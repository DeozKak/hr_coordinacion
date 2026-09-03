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

    /** Valor de tbl_insp_cali.state con el que un inspector cuenta como activo. */
    private const ACTIVO = 1;

    /**
     * Técnicos asignados, agrupados por municipio madre y sin repetidos dentro del grupo.
     *
     * Sólo cuentan los inspectores activos. El cruce es un join interno a
     * propósito: una asignación cuyo inspector ya no existe tampoco es fuerza
     * de trabajo, y antes se colaba con el nombre en blanco.
     *
     * El filtro aquí es la segunda línea de defensa: al desactivar a alguien se
     * le retira de su localidad (lo hace tbl_insp_cali), pero si una fila se
     * quedara por el camino —una baja hecha a mano en la base, por ejemplo—
     * tampoco debe aparecer en la tarjeta.
     *
     * @return Collection Municipio madre => colección de técnicos con su supervisor.
     */
    public function tecnicosPorLocalidad(): Collection
    {
        $tecnicos_brutos = AsignacionTecnicoLocalidad::join('tbl_insp_cali', 'tbl_asignacion_tecnicos_localidad.id_tecnico', '=', 'tbl_insp_cali.id')
            ->where('tbl_insp_cali.state', self::ACTIVO)
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
     * La fuerza de trabajo tal como la pinta la tarjeta del tablero.
     *
     * Se arma aquí y no en la plantilla porque hay dos caminos que llegan a la
     * misma tabla —el HTML de la primera carga y la respuesta JSON de guardar
     * una asignación— y tienen que dar exactamente la misma forma; si no, la
     * tabla se vería distinta antes y después de editar.
     *
     * No se ordena a propósito: se respeta el orden en que salen del grupo,
     * que es el que se veía hasta ahora.
     *
     * @return list<array{localidad: string, total: int, tecnicos: list<array>, ids: list<int|string>}>
     */
    public function localidadesParaVista(): array
    {
        return $this->tecnicosPorLocalidad()
            ->map(fn (Collection $tecnicos, string $localidad) => [
                'localidad' => $localidad,
                'total'     => $tecnicos->count(),
                'tecnicos'  => $tecnicos->map(fn ($t) => [
                    'id'         => $t->ID_TECNICO,
                    'nombre'     => $t->NOMBRE_COMPLETO ?? 'Nombre no registrado',
                    'supervisor' => $t->supervisor->name ?? '—',
                ])->values()->all(),
                'ids'       => $tecnicos->pluck('ID_TECNICO')->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * El catálogo de técnicos como lo espera el selector del modal.
     *
     * Mismo motivo que localidadesParaVista(): al guardar hay que devolverlo
     * otra vez para que las etiquetas de "actualmente en" queden al día.
     *
     * @return list<array{id: mixed, nombre: string, asignado_en: ?string}>
     */
    public function catalogoParaVista(): array
    {
        return $this->todosLosTecnicos()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'nombre'      => $t->NOMBRE_COMPLETO,
                'asignado_en' => $t->asignado_en,
            ])
            ->values()
            ->all();
    }

    /**
     * Catálogo de técnicos que se pueden asignar, con la localidad de cada uno.
     *
     * Sólo los activos: a un inspector dado de baja en gestión de inspectores
     * no tiene sentido ofrecerlo en el selector.
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
            ->where('state', self::ACTIVO)
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
