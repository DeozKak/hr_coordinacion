<?php

namespace App\Services\Home;

use App\Models\CausalLegalizacion;
use App\Models\CorteGdo;
use DateTime;
use Illuminate\Support\Facades\DB;

class CorteGdoService
{
    /** Tabla de la que sale lo legalizado. */
    private const TABLA = 'tbl_cerradas';

    public function __construct(
        private LimpiezaMunicipioService $municipios,
        private TiposTrabajoService $tipos,
        private FechaEjecucionService $fechas
    ) {}

    /**
     * El corte vigente y todo lo que la vista necesita de él.
     *
     * Devuelve null en `corte` mientras no se haya definido ninguno: la vista
     * lo usa para enseñar el aviso de que hay que crear el primero.
     *
     * @param string $localidadSeleccionada Municipio madre a filtrar, o 'TODAS'.
     * @return array corte, metricas, detalles.
     */
    public function generar(string $localidadSeleccionada): array
    {
        $corte = CorteGdo::vigente();

        if (! $corte) {
            return [
                'corte'    => null,
                'metricas' => ['legalizado_corte' => 0],
                'detalles' => ['legalizado_corte' => []],
            ];
        }

        $legalizadas = $this->legalizadasDelCorte($corte, $localidadSeleccionada);

        return [
            'corte'    => $this->resumen($corte),
            'metricas' => ['legalizado_corte' => count($legalizadas)],
            'detalles' => ['legalizado_corte' => $legalizadas],
        ];
    }

    /**
     * Datos del corte para la tarjeta: fechas, rótulo y días que faltan.
     *
     * @return array<string, mixed>
     */
    public function resumen(CorteGdo $corte): array
    {
        return [
            'id'              => $corte->id,
            'inicio'          => $corte->fecha_inicio->format('Y-m-d'),
            'fin'             => $corte->fecha_fin->format('Y-m-d'),
            'inicio_mostrado' => $corte->fecha_inicio->format('d/m/Y'),
            'fin_mostrado'    => $corte->fecha_fin->format('d/m/Y'),
            'dias_restantes'  => $this->diasRestantes($corte),
            'cerrado'         => $corte->fecha_fin->format('Y-m-d') < date('Y-m-d'),
        ];
    }

    /**
     * Días que faltan para que termine el corte.
     *
     * Cero el último día —hoy todavía cuenta— y cero también si ya pasó: para
     * un corte cerrado el dato que importa es `cerrado`, no un número negativo.
     */
    public function diasRestantes(CorteGdo $corte): int
    {
        $hoy = new DateTime(date('Y-m-d'));
        $fin = new DateTime($corte->fecha_fin->format('Y-m-d'));

        if ($fin <= $hoy) {
            return 0;
        }

        return (int) $hoy->diff($fin)->days;
    }

    /**
     * Fecha desde la que se acumula: el inicio del corte vigente.
     *
     * Es lo que acota el acumulado de pendientes por legalizar y prioridades.
     * Sin corte definido devuelve null y quien la use decide qué hacer.
     */
    public function inicioDelCorte(): ?string
    {
        $corte = CorteGdo::vigente();

        return $corte ? $corte->fecha_inicio->format('Y-m-d') : null;
    }

    /**
     * Órdenes legalizadas dentro del corte, ya listas para el modal.
     *
     * FECHA_LEGALIZACION es varchar con formato "Y-m-d H:i:s", así que el rango
     * se compara como cadena: con ese formato el orden alfabético coincide con
     * el cronológico y el índice de la columna sirve igual.
     *
     * @return list<array<string, mixed>>
     */
    private function legalizadasDelCorte(CorteGdo $corte, string $localidadSeleccionada): array
    {
        $desde = $corte->fecha_inicio->format('Y-m-d') . ' 00:00:00';
        $hasta = $corte->fecha_fin->format('Y-m-d') . ' 23:59:59';

        $filas = DB::table(self::TABLA)
            ->select('CONTRATO', 'NUMERO_ORDEN', 'NOMBRE_TECNICO', 'ID_TIPO_TRABAJO',
                     'DESC_LOCALIDAD', 'DESCCAUSAL', 'FECHA_LEGALIZACION')
            ->whereBetween('FECHA_LEGALIZACION', [$desde, $hasta])
            ->orderBy('FECHA_LEGALIZACION')
            ->get();

        return $filas
            /* Sólo las que de verdad legalizan: el archivo trae también
               cierres que no lo son y contarlos inflaba la cifra del corte. */
            ->filter(fn ($fila) => CausalLegalizacion::legaliza($fila->DESCCAUSAL))
            // El filtro de municipio se aplica sobre el municipio madre, no
            // sobre la localidad cruda, igual que en el resto del tablero.
            ->filter(fn ($fila) => $localidadSeleccionada === 'TODAS'
                || $this->municipios->limpiar($fila->DESC_LOCALIDAD) === $localidadSeleccionada)
            ->map(fn ($fila) => [
                'contrato'    => ltrim((string) $fila->CONTRATO, ':'),
                'orden'       => $fila->NUMERO_ORDEN,
                'operario'    => $fila->NOMBRE_TECNICO ?: '-',
                'tarea'       => $this->tipos->etiqueta($fila->ID_TIPO_TRABAJO),
                'localidad'   => $this->municipios->limpiar($fila->DESC_LOCALIDAD),
                'causal'      => $fila->DESCCAUSAL ?: '-',
                'fecha'       => $this->fechas->mostrar($fila->FECHA_LEGALIZACION),
                'fecha_orden' => $fila->FECHA_LEGALIZACION ?? '',
            ])
            ->values()
            ->all();
    }
}
