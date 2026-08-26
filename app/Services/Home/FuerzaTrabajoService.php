<?php

namespace App\Services\Home;

use App\Models\Zonificacion\AsignacionTecnicoLocalidad;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FuerzaTrabajoService
{
    public function __construct(
        private LimpiezaMunicipioService $municipios
    ) {}

    /**
     * Técnicos asignados, agrupados por municipio madre y sin repetidos dentro del grupo.
     *
     * @return Collection Municipio madre => colección de técnicos con su supervisor.
     */
    public function tecnicosPorLocalidad(): Collection
    {
        $tecnicos_brutos = AsignacionTecnicoLocalidad::leftJoin('tbl_insp_cali', 'tbl_asignacion_tecnicos_localidad.id_tecnico', '=', 'tbl_insp_cali.id')
            ->leftJoin('users', 'tbl_insp_cali.SUPERVISOR', '=', 'users.id')
            ->select(
                'tbl_asignacion_tecnicos_localidad.localidad AS DESC_LOCALIDAD',
                'tbl_asignacion_tecnicos_localidad.id_tecnico AS ID_TECNICO',
                DB::raw("CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS NOMBRE_COMPLETO"),
                'users.name AS supervisor_name'
            )
            ->get()
            ->map(function ($tec) {
                $tec->supervisor = (object) ['name' => $tec->supervisor_name ?? 'Sin Supervisor asignado'];
                $tec->MUNICIPIO_MADRE = $this->municipios->limpiar($tec->DESC_LOCALIDAD);
                return $tec;
            });

        return $tecnicos_brutos->groupBy('MUNICIPIO_MADRE')->map(function ($grupo) {
            return $grupo->unique('ID_TECNICO')->values();
        });
    }

    /**
     * Catálogo completo de técnicos, marcando en qué localidad quedó asignado cada uno.
     *
     * @return Collection Técnicos con id, NOMBRE_COMPLETO y asignado_en.
     */
    public function todosLosTecnicos(): Collection
    {
        $asignaciones_totales = AsignacionTecnicoLocalidad::all()->pluck('localidad', 'id_tecnico');

        return DB::table('tbl_insp_cali')
            ->select('id', DB::raw("CONCAT(apellidos, ' ', nombres) AS NOMBRE_COMPLETO"))
            ->whereNotNull('id')
            ->where('id', '!=', '100')
            ->orderBy('apellidos')
            ->get()
            ->map(function ($t) use ($asignaciones_totales) {
                $t->asignado_en = $asignaciones_totales[$t->id] ?? null;
                return $t;
            });
    }
}
