<?php

namespace App\Services\Produccion;

use App\Models\Produccion\TblProduccionCorte;
use App\Models\Produccion\TblProduccionHistorico;
use App\Models\TblInspCali;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * La tabla de detalle del corte: una fila por inspector con su producción.
 *
 * El resultado se guarda en tbl_produccion_historico y se devuelve leído de
 * ahí, no del cálculo: así la pantalla y lo archivado son siempre lo mismo.
 */
class DetalleDelCorteService
{
    public function __construct(
        private ContratosDelCorteService $contratos,
        private CalendarioDeCorteService $calendario,
        private DoblesDeSabadoService $dobles
    ) {}

    /**
     * @return array|null null cuando no hay corte con el que trabajar.
     */
    public function generar(TblProduccionCorte $corte): ?array
    {
        $diasIntermedios = $this->calendario->diasIntermedios($corte);

        if ($diasIntermedios === null) {
            return null;
        }

        $historico = TblProduccionHistorico::where('id_corte', $corte->id)->first();
        $diasFestivos = $this->calendario->diasFestivos($corte);

        /* Los inspectores de esta pantalla se sacan sin excluir las líneas
           matriz: un inspector que solo hizo matrices igual tiene que salir en
           la tabla, aunque su producción quede en cero. */
        $inspectores = $this->contratos->inspectoresConProduccion($corte, sinMatrices: false);

        $porInspector = $this->contratos->consulta($corte)
            ->whereIn('CC_OPERARIO', $inspectores->pluck('cedula'))
            ->select('CC_OPERARIO', 'FECHA', 'CATEGORIA', '4_RECINTOS', 'TIPO_TRABAJO', 'diseno_especial')
            ->get()
            ->groupBy('CC_OPERARIO');

        $filas = [];
        $sabados = [];

        foreach ($inspectores as $inspector) {
            $suyos = $porInspector->get($inspector->cedula, collect());

            if ($suyos->isEmpty()) {
                continue;
            }

            $calculo = $this->calcularDobles($inspector, $corte, $diasFestivos, $historico);
            $sabados[] = ['datos' => $calculo['sabadosdobles']];

            $fila = $this->filaDelInspector($inspector, $suyos, $corte, $diasFestivos, $historico, $calculo);

            if ($fila !== null) {
                $filas[] = $fila;
            }
        }

        $respuesta = [
            'diasIntermedios'       => $diasIntermedios,
            'produccionInspector'   => $filas,
            'diasFestivos'          => $diasFestivos,
            'sabadodobles'          => $this->quitarExcluidos($sabados, $historico),
            'fechasIntermedias'     => $this->calendario->fechas($corte),
            'corte'                 => $corte->id,
            'sabadosDoblesManuales' => array_values(json_decode($historico?->dobles_sabados ?? '', true) ?? []),
        ];

        return $this->archivar($corte, $respuesta);
    }

    /** Una fila de la tabla, o null si el inspector no debe aparecer. */
    private function filaDelInspector(
        TblInspCali $inspector,
        Collection $suyos,
        TblProduccionCorte $corte,
        array $diasFestivos,
        ?TblProduccionHistorico $historico,
        array $calculo
    ): ?array {
        $porDia = $this->contratosPorDia($suyos);

        $fechas = $this->calendario->plantillaDeFechas($corte);
        $contadorFestivos = null;
        $suma = 0;

        foreach ($porDia as $dia) {
            if (in_array($dia['fecha'], $diasFestivos, false)) {
                $contadorFestivos += $dia['total'];
            }

            $fechas[$dia['fecha']] = $dia['total'];
            $suma += $dia['total'];
        }

        $fila = [
            'cedula'  => $inspector->cedula,
            'nombres' => $inspector->apellidos . ' ' . $inspector->nombres,
        ];

        $diasLaborados = 0;

        foreach ($fechas as $fecha => $total) {
            $fila[$fecha] = $total;

            if ($total > 0) {
                $diasLaborados++;
            }
        }

        // Un inspector inactivo que no produjo nada no ocupa una fila.
        if ($suma === 0 && $inspector->state === 0) {
            return null;
        }

        $totalFestivos = $contadorFestivos + $calculo['contadorDiasSabados'];

        if ($totalFestivos === 0) {
            $totalFestivos = null;
        }

        $ajustes = $this->ajustesManuales($historico, $inspector->cedula);

        $fila['sub_total']          = $suma;
        $fila['matrices']           = $this->contarONull($suyos->whereIn('TIPO_TRABAJO', ContratosDelCorteService::MATRICES)->count());
        $fila['festivos']           = $totalFestivos - $ajustes['festivos'] + $ajustes['sabados'];
        $fila['diseños_especiales'] = $this->contarONull($suyos->where('diseno_especial', 1)->count());
        $fila['4_recintos']         = $this->recintosEquivalentes($suyos);
        $fila['comerciales']        = $this->contarONull($suyos->where('CATEGORIA', 'COMERCIAL')->count());
        $fila['total'] = $fila['comerciales'] + $fila['4_recintos'] + $fila['festivos']
            + $fila['sub_total'] + $fila['matrices'] + $fila['diseños_especiales'];
        $fila['nuevas']          = $this->contarONull($suyos->where('TIPO_TRABAJO', 'RN 12162')->count());
        $fila['dias_laborados']  = $diasLaborados;
        $fila['promedio']        = number_format($fila['sub_total'] / $fila['dias_laborados'], 1);
        $fila['meta']            = $corte->meta;
        $fila['porcentaje_meta'] = '%' . number_format(($fila['sub_total'] / $fila['meta']) * 100, 2);

        return $fila;
    }

    /**
     * Contratos por día, sin contar las líneas matriz.
     *
     * @return array<int, array{fecha: string, total: int}>
     */
    private function contratosPorDia(Collection $suyos): array
    {
        return $suyos
            ->groupBy(fn ($contrato) => Carbon::parse($contrato->FECHA)->format('Y-m-d'))
            ->map(fn ($grupo) => [
                'fecha' => $grupo->first()->FECHA,
                'total' => $grupo->whereNotIn('TIPO_TRABAJO', ContratosDelCorteService::MATRICES)->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * Los recintos de más, convertidos a inspecciones equivalentes.
     *
     * La columna guarda cuántos recintos tenía el predio; cada cuatro suman una
     * inspección. Se trunca hacia abajo y un cero se guarda como vacío para que
     * la celda salga en blanco en vez de con un 0.
     */
    private function recintosEquivalentes(Collection $suyos): ?int
    {
        $total = null;

        foreach ($suyos->groupBy('4_RECINTOS') as $recintos => $grupo) {
            if ($recintos != 'NO') {
                $total = $total + ($grupo->count() * $recintos);
            }
        }

        $equivalentes = (int) floor($total / 4);

        return $equivalentes === 0 ? null : $equivalentes;
    }

    /** Un cero se guarda como vacío para que la celda salga en blanco. */
    private function contarONull(int $cantidad): ?int
    {
        return $cantidad > 0 ? $cantidad : null;
    }

    /**
     * Lo que coordinación quitó o añadió a mano para este inspector.
     *
     * @return array{festivos: int, sabados: int}
     */
    private function ajustesManuales(?TblProduccionHistorico $historico, $cedula): array
    {
        $festivos = 0;
        $sabados = 0;

        $noDoblesFestivos = json_decode($historico?->no_dobles_festivos ?? '', true);
        $doblesSabados = json_decode($historico?->dobles_sabados ?? '', true);

        if ($noDoblesFestivos !== null) {
            foreach ($noDoblesFestivos['noDoblesFestivos'] as $registro) {
                foreach ($registro['datos']['totalInspecciones'] as $inspeccion) {
                    if ($registro['datos']['cc_inspector'] == $cedula) {
                        $festivos += $inspeccion['total_contratos'];
                    }
                }
            }
        }

        if ($doblesSabados !== null) {
            foreach ($doblesSabados['doblesSabados'] as $registro) {
                foreach ($registro['datos']['totalInspecciones'] as $inspeccion) {
                    if ($registro['datos']['cc_inspector'] == $cedula) {
                        $sabados += intval($inspeccion['total_contratos']);
                    }
                }
            }
        }

        return ['festivos' => $festivos, 'sabados' => $sabados];
    }

    private function calcularDobles(
        TblInspCali $inspector,
        TblProduccionCorte $corte,
        array $diasFestivos,
        ?TblProduccionHistorico $historico
    ): array {
        return $this->dobles->calcular(
            $inspector,
            Carbon::parse($corte->fecha_inicio)->format('Y-m-d'),
            Carbon::parse($corte->fecha_fin)->format('Y-m-d'),
            $diasFestivos,
            $corte->dobles,
            $historico
        );
    }

    /**
     * Quita de la lista de sábados dobles los que coordinación excluyó a mano.
     *
     * El cálculo los mete y aquí se sacan, en vez de no meterlos: la lista de
     * exclusiones puede cambiar entre una carga y otra y así el filtro se
     * aplica siempre sobre el cálculo recién hecho.
     */
    private function quitarExcluidos(array $sabados, ?TblProduccionHistorico $historico): array
    {
        $excluidos = json_decode($historico?->no_dobles ?? '', true);

        if ($excluidos === null) {
            return $sabados;
        }

        foreach ($sabados as &$sabado) {
            foreach ($sabado['datos'] as $indice => $dato) {
                foreach ($excluidos as $grupo) {
                    foreach ($grupo as $registro) {
                        if ($dato['cc_inspector'] == $registro['datos']['cc_inspector']
                            && in_array($dato['fecha'], $registro['datos']['fechas'], false)) {
                            unset($sabado['datos'][$indice]);
                        }
                    }
                }
            }

            $sabado['datos'] = array_values($sabado['datos']);
        }

        return array_values($sabados);
    }

    /** Guarda el resultado en el histórico y devuelve lo que quedó archivado. */
    private function archivar(TblProduccionCorte $corte, array $respuesta): array
    {
        // Sin firstOrNew: el modelo no declara $fillable y no admite asignación masiva.
        $historico = TblProduccionHistorico::where('id_corte', $corte->id)->first()
            ?? new TblProduccionHistorico();

        $historico->id_corte = $corte->id;
        $historico->data = json_encode($respuesta);
        $historico->save();

        return json_decode($historico->data, true);
    }
}
