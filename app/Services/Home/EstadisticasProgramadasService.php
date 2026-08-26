<?php

namespace App\Services\Home;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstadisticasProgramadasService
{
    public function __construct(
        private LimpiezaMunicipioService $municipios
    ) {}

    /**
     * Estadísticas de las programaciones agendadas para un día, por tipo de trabajo.
     *
     * @param string $fechaReporte Fecha de agendamiento en formato Y-m-d.
     * @param string $localidadSeleccionada Municipio madre a filtrar, o 'TODAS'.
     * @return array estadisticas (una fila por tipo), totales y detalles por tipo.
     */
    public function generar(string $fechaReporte, string $localidadSeleccionada): array
    {
        $pendientesRaw = $this->programacionesPlantilla($fechaReporte)
            ->concat($this->programacionesBusqueda($fechaReporte));

        $ejecutadasRaw = $this->programacionesEjecutadas($fechaReporte);

        $pendientesRaw = $this->filtrarPorLocalidad($pendientesRaw, $localidadSeleccionada);
        $ejecutadasRaw = $this->filtrarPorLocalidad($ejecutadasRaw, $localidadSeleccionada);

        // Una orden de trabajo puede venir por más de una fuente: se cuenta una sola vez
        $pendientes = $pendientesRaw->unique('ORDEN_TRABAJO')->values();
        $ejecutadas = $ejecutadasRaw->unique('ORDEN_TRABAJO')->values();

        $tiposDeTrabajo = $pendientes->pluck('TIPO_TRABAJO')
            ->merge($ejecutadas->pluck('TIPO_TRABAJO'))
            ->unique();

        $estadisticasProgramadas = [];
        $detallesProgramaciones = [];
        $totalesProg = ['programadas' => 0, 'ejecutadas' => 0, 'pendientes' => 0];

        foreach ($tiposDeTrabajo as $tipo) {
            $nombreTipo = $tipo ? $tipo : 'SIN TIPO';

            $pendientesDelTipo = $pendientes->where('TIPO_TRABAJO', $tipo);
            $ejecutadasDelTipo = $ejecutadas->where('TIPO_TRABAJO', $tipo);

            $cantPend = $pendientesDelTipo->count();
            $cantEjec = $ejecutadasDelTipo->count();
            $total = $cantPend + $cantEjec;

            $estadisticasProgramadas[] = [
                'tipo'       => $nombreTipo,
                'total'      => $total,
                'ejecutadas' => $cantEjec,
                'pendientes' => $cantPend,
            ];

            $detallesProgramaciones[$nombreTipo] = [
                'pendientes' => $this->mapearDetalle($pendientesDelTipo),
                'ejecutadas' => $this->mapearDetalle($ejecutadasDelTipo),
            ];

            $totalesProg['programadas'] += $total;
            $totalesProg['ejecutadas']  += $cantEjec;
            $totalesProg['pendientes']  += $cantPend;
        }

        return [
            'estadisticas' => $estadisticasProgramadas,
            'totales'      => $totalesProg,
            'detalles'     => $detallesProgramaciones,
        ];
    }

    /**
     * Contratos de programaciones ya finalizadas por el usuario y aún sin recepcionar.
     */
    private function programacionesBusqueda(string $fechaReporte): Collection
    {
        return DB::table('tbl_programacion_contratos AS pc')
            ->join('tbl_programacion_base AS pb', 'pc.CONTRATO', '=', 'pb.CONTRATO')
            ->join('tbl_programacion_usuarios AS pu', 'pc.id_programacion', '=', 'pu.id')
            ->where('pc.FECHA_AGENDAMIENTO', '=', $fechaReporte)
            ->where('pc.EJECUTADA', '=', 0)
            ->where('pu.finished', 1)
            ->where('pb.ESTADO_RECEPCION', '=', 0)
            ->select('pc.TIPO_TRABAJO', 'pc.CIUDAD', 'pc.CONTRATO', 'pc.NOMBRE_USUARIO', 'pc.TECNICO', 'pc.ORDEN_TRABAJO')
            ->get();
    }

    /**
     * Contratos cargados por plantilla, pendientes de ejecutar.
     */
    private function programacionesPlantilla(string $fechaReporte): Collection
    {
        return DB::table('tbl_programacion_contratos')
            ->where('FECHA_AGENDAMIENTO', '=', $fechaReporte)
            ->where('EJECUTADA', '=', 0)
            ->where('plantilla', 1)
            ->select('TIPO_TRABAJO', 'CIUDAD', 'CONTRATO', 'NOMBRE_USUARIO', 'TECNICO', 'ORDEN_TRABAJO')
            ->get();
    }

    /**
     * Contratos agendados que ya fueron marcados como ejecutados.
     */
    private function programacionesEjecutadas(string $fechaReporte): Collection
    {
        return DB::table('tbl_programacion_contratos')
            ->where('FECHA_AGENDAMIENTO', '=', $fechaReporte)
            ->where('EJECUTADA', '=', 1)
            ->select('TIPO_TRABAJO', 'CIUDAD', 'CONTRATO', 'NOMBRE_USUARIO', 'TECNICO', 'ORDEN_TRABAJO')
            ->get();
    }

    /**
     * Filtra comparando contra el municipio madre, no contra la ciudad cruda.
     */
    private function filtrarPorLocalidad(Collection $programaciones, string $localidadSeleccionada): Collection
    {
        if ($localidadSeleccionada === 'TODAS') {
            return $programaciones;
        }

        return $programaciones->filter(function ($item) use ($localidadSeleccionada) {
            return $this->municipios->limpiar($item->CIUDAD) === $localidadSeleccionada;
        });
    }

    /**
     * Filas que consume el modal de programaciones en la vista.
     */
    private function mapearDetalle(Collection $programaciones): array
    {
        return $programaciones->map(function ($item) {
            return [
                'contrato' => $item->CONTRATO,
                'cliente'  => $item->NOMBRE_USUARIO ?? 'Sin Registro',
                'tecnico'  => $item->TECNICO ?? 'Sin Asignar',
                'ciudad'   => $this->municipios->limpiar($item->CIUDAD),
            ];
        })->values()->toArray();
    }
}
