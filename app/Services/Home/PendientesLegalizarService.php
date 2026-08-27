<?php

namespace App\Services\Home;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PendientesLegalizarService
{
    /**
     * Meses de vencimiento a partir de los cuales el contrato se marca como prioridad.
     */
    private const MESES_PARA_PRIORIDAD = 60;

    /**
     * Contratos por consulta al cruzar contra cerradas.
     */
    private const CONTRATOS_POR_BLOQUE = 1000;

    /**
     * Tipos de trabajo que no se legalizan, así que nunca cuentan como pendientes.
     */
    private const TIPOS_SIN_LEGALIZACION = [
        'FI-29 revisión periódica línea matriz',
        'FI-31 REVISIÓN NUEVA LINEA MATRIZ',
    ];

    public function __construct(
        private LimpiezaMunicipioService $municipios,
        private FechaEjecucionService $fechas
    ) {}

    /**
     * Cruza las inspecciones ejecutadas contra cerradas para saber qué falta legalizar.
     *
     * Una ejecutada queda pendiente de legalizar mientras no aparezca en tbl_cerradas
     * para el mismo tipo de trabajo.
     *
     * El cruce solo define si está legalizado o no. Los meses de vencimiento salen
     * del propio reporte diario, que ya los trae calculados en la columna Meses.
     *
     * @param iterable $ejecutadas Reportes con cierre efectivo del día.
     * @return array metricas y detalles de pendientes_legalizar y prioridades.
     */
    public function calcular(iterable $ejecutadas): array
    {
        $metricas = ['pendientes_legalizar' => 0, 'prioridades' => 0];
        $detalles = ['pendientes_legalizar' => [], 'prioridades' => []];

        $ejecutadasList = collect($ejecutadas);

        if ($ejecutadasList->isEmpty()) {
            return ['metricas' => $metricas, 'detalles' => $detalles];
        }

        $contratosEfectivos = $ejecutadasList->map(function ($r) {
            return ltrim($r->NroSitio, ':');
        })->unique()->toArray();

        $cerradas = $this->agruparPorContrato(
            'tbl_cerradas',
            $contratosEfectivos,
            ['CONTRATO', 'ID_TIPO_TRABAJO']
        );

        $tiposSinLegalizacion = array_map(
            fn (string $tipo) => $this->normalizarTipo($tipo),
            self::TIPOS_SIN_LEGALIZACION
        );

        foreach ($ejecutadasList as $rep) {
            $contrato = ltrim($rep->NroSitio, ':');
            $tarea = trim(substr($rep->TipoTarea, 2));

            if (in_array($this->normalizarTipo($rep->TipoTarea), $tiposSinLegalizacion, true)) {
                continue;
            }

            // Legalizada es la que ya aparece en cerradas; el resto queda pendiente.
            // No se exige estar en tbl_asignaciones: esa tabla es la foto de las OT
            // abiertas del día y una orden recién ejecutada ya salió de ahí sin haber
            // llegado todavía a cerradas.
            if ($this->buscarCoincidencia($cerradas, $contrato, $tarea)) {
                continue;
            }

            $metricas['pendientes_legalizar']++;
            $detalles['pendientes_legalizar'][] = $this->infoModal($rep, $contrato);

            if ($this->esPrioridad($rep)) {
                $metricas['prioridades']++;
                $detalles['prioridades'][] = $this->infoModal($rep, $contrato);
            }
        }

        return ['metricas' => $metricas, 'detalles' => $detalles];
    }

    /**
     * Trae los registros de una tabla para los contratos dados, agrupados por contrato.
     *
     * La consulta se parte en bloques porque el acumulado histórico puede traer
     * miles de contratos y un IN gigantesco no lo aguanta el motor.
     *
     * @param array $contratos Contratos a buscar.
     * @param array $columnas Columnas a traer.
     */
    private function agruparPorContrato(string $tabla, array $contratos, array $columnas): Collection
    {
        return collect($contratos)
            ->chunk(self::CONTRATOS_POR_BLOQUE)
            ->flatMap(fn (Collection $bloque) => DB::table($tabla)
                ->whereIn('CONTRATO', $bloque->all())
                ->get($columnas))
            ->groupBy('CONTRATO');
    }

    /**
     * Busca el registro del contrato que corresponde al tipo de trabajo buscado.
     *
     * El tipo 10444 y el 12161 se consideran equivalentes.
     *
     * @param Collection $lista Registros agrupados por CONTRATO.
     * @return object|null
     */
    private function buscarCoincidencia(Collection $lista, string $contrato, string $tareaBuscada)
    {
        if (!isset($lista[$contrato])) {
            return null;
        }

        $equivalentes = ['10444', '12161'];
        $aceptaEquivalente = in_array($tareaBuscada, $equivalentes, true);

        foreach ($lista[$contrato] as $item) {
            if ($item->ID_TIPO_TRABAJO == $tareaBuscada) {
                return $item;
            }

            // La equivalencia va en ambos sentidos: el reporte puede traer cualquiera de los dos
            if ($aceptaEquivalente && in_array((string) $item->ID_TIPO_TRABAJO, $equivalentes, true)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Deja el tipo de trabajo comparable: sin tildes, en mayúsculas y con un solo espacio
     * entre palabras, porque el mismo tipo llega escrito distinto según la fuente.
     */
    private function normalizarTipo(?string $texto): string
    {
        $texto = mb_strtoupper(trim((string) $texto), 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/u', ' ', $texto);
    }

    /**
     * Es prioridad cuando los meses de vencimiento del reporte llegan al umbral definido.
     */
    private function esPrioridad(object $rep): bool
    {
        return is_numeric($rep->Meses) && (int) $rep->Meses >= self::MESES_PARA_PRIORIDAD;
    }

    /**
     * Fila que consume el modal de detalles en la vista.
     */
    private function infoModal(object $rep, string $contrato): array
    {
        return [
            'contrato'    => $contrato,
            'operario'    => $rep->NombreOperario,
            'tarea'       => $rep->TipoTarea,
            'meses'       => $rep->Meses,
            'cierre'      => $rep->Cierre3,
            'localidad'   => $this->municipios->limpiar($rep->Localidad),
            'fecha'       => $this->fechas->mostrar($rep->FechaRealFin ?? null),
            'fecha_orden' => $rep->FechaRealFin ?? '',
        ];
    }
}
