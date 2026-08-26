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

    /**
     * Deja la localidad con exactamente los técnicos indicados.
     *
     * Un técnico solo puede estar en una localidad a la vez, así que si venía
     * de otra se le retira de allá automáticamente: no hace falta desasignarlo
     * primero para poder traérselo.
     *
     * @param string $localidad Nombre de la localidad destino.
     * @param array $idsTecnicos Ids de los técnicos que quedan en la localidad.
     * @return array asignados (total) y movidos (nombre => localidad de origen).
     */
    public function asignar(string $localidad, array $idsTecnicos): array
    {
        $localidad   = strtoupper(trim($localidad));
        $idsTecnicos = array_values(array_unique(array_filter($idsTecnicos)));

        return DB::transaction(function () use ($localidad, $idsTecnicos) {
            $movidos = $this->tecnicosDeOtraLocalidad($localidad, $idsTecnicos);

            // Se limpia la localidad destino y de paso se retira a los
            // seleccionados de donde estuvieran, para no duplicarlos
            AsignacionTecnicoLocalidad::where('localidad', $localidad)->delete();

            if (!empty($idsTecnicos)) {
                AsignacionTecnicoLocalidad::whereIn('id_tecnico', $idsTecnicos)->delete();

                foreach ($idsTecnicos as $idTecnico) {
                    AsignacionTecnicoLocalidad::create([
                        'localidad'  => $localidad,
                        'id_tecnico' => $idTecnico,
                    ]);
                }
            }

            return [
                'asignados' => count($idsTecnicos),
                'movidos'   => $movidos,
            ];
        });
    }

    /**
     * De los técnicos seleccionados, cuáles venían de una localidad distinta.
     *
     * @return array<string, string> Nombre del técnico => localidad de origen.
     */
    private function tecnicosDeOtraLocalidad(string $localidad, array $idsTecnicos): array
    {
        if (empty($idsTecnicos)) {
            return [];
        }

        return AsignacionTecnicoLocalidad::leftJoin('tbl_insp_cali', 'tbl_asignacion_tecnicos_localidad.id_tecnico', '=', 'tbl_insp_cali.id')
            ->whereIn('tbl_asignacion_tecnicos_localidad.id_tecnico', $idsTecnicos)
            ->where('tbl_asignacion_tecnicos_localidad.localidad', '!=', $localidad)
            ->select(
                'tbl_asignacion_tecnicos_localidad.localidad',
                'tbl_asignacion_tecnicos_localidad.id_tecnico',
                DB::raw("CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS NOMBRE_COMPLETO")
            )
            ->get()
            ->mapWithKeys(fn ($fila) => [
                trim($fila->NOMBRE_COMPLETO) ?: ('Técnico ' . $fila->id_tecnico) => $fila->localidad,
            ])
            ->all();
    }
}
