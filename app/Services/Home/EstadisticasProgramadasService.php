<?php

namespace App\Services\Home;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstadisticasProgramadasService
{
    public function __construct(
        private LimpiezaMunicipioService $municipios,
        private TiposTrabajoService $tipos
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

        /* El selector se arma con el día completo y sin el filtro puesto, igual
           que el de la cabecera: sólo aparecen los municipios que de verdad
           tienen programación ese día, no los 29 que existen en la tabla. */
        $ciudades = $this->municipiosDelDia($pendientesRaw->concat($ejecutadasRaw));

        $pendientesRaw = $this->filtrarPorLocalidad($pendientesRaw, $localidadSeleccionada);
        $ejecutadasRaw = $this->filtrarPorLocalidad($ejecutadasRaw, $localidadSeleccionada);

        $pendientes = $pendientesRaw->unique(function ($item) {
            $orden = trim(strtoupper($item->ORDEN_TRABAJO ?? ''));

            // Si la orden es N/A, usamos su ID real de base de datos.
            // Así evitamos borrar plantillas distintas, pero eliminamos duplicados exactos.
            if ($orden === 'N/A' || $orden === '') {
                return 'PLANTILLA_' . $item->id;
            }

            return $item->ORDEN_TRABAJO;
        })->values();

        $ejecutadas = $ejecutadasRaw->unique(function ($item) {
            $orden = trim(strtoupper($item->ORDEN_TRABAJO ?? ''));

            if ($orden === 'N/A' || $orden === '') {
                return 'PLANTILLA_' . $item->id;
            }

            return $item->ORDEN_TRABAJO;
        })->values();

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
                // Con sus iniciales: RP, RN o SA. `tipo` sigue siendo la clave
                // con la que la vista pide el detalle, así que no se toca.
                'etiqueta'   => $this->tipos->etiqueta($nombreTipo),
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
            'ciudades'     => $ciudades,
        ];
    }

    /**
     * Municipios madre con programación en el día, listos para el selector.
     *
     * Se saca de las mismas filas que cuenta la tarjeta, así que lo que se
     * ofrece filtrar siempre devuelve algo. Antes la lista salía de un
     * DISTINCT sobre toda la tabla: 29 municipios, de los que un día cualquiera
     * sólo ocho tenían programación.
     *
     * Los nombres se normalizan a su municipio madre —la columna trae
     * corregimientos y direcciones— y se descarta lo que no sea una madre,
     * porque por un corregimiento no se filtra.
     *
     * @return array<int, string> Nombres únicos y ordenados.
     */
    private function municipiosDelDia(Collection $programaciones): array
    {
        return $programaciones
            ->pluck('CIUDAD')
            ->map(fn ($ciudad) => $this->municipios->limpiar($ciudad))
            ->filter(fn (string $ciudad) => $ciudad !== '' && $this->municipios->esMadre($ciudad))
            ->unique()
            ->sort()
            ->values()
            ->all();
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
            ->select('pc.id','pc.TIPO_TRABAJO', 'pc.CIUDAD', 'pc.CONTRATO', 'pc.NOMBRE_USUARIO', 'pc.TECNICO', 'pc.ORDEN_TRABAJO')
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
            ->select('id','TIPO_TRABAJO', 'CIUDAD', 'CONTRATO', 'NOMBRE_USUARIO', 'TECNICO', 'ORDEN_TRABAJO')
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
            ->select('id','TIPO_TRABAJO', 'CIUDAD', 'CONTRATO', 'NOMBRE_USUARIO', 'TECNICO', 'ORDEN_TRABAJO')
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
                'orden'    => $item->ORDEN_TRABAJO,
                'cliente'  => $item->NOMBRE_USUARIO ?? 'Sin Registro',
                'tecnico'  => $item->TECNICO ?? 'Sin Asignar',
                'ciudad'   => $this->municipios->limpiar($item->CIUDAD),
            ];
        })->values()->toArray();
    }
}
