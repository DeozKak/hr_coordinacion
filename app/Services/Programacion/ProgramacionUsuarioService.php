<?php

namespace App\Services\Programacion;

use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Programacion\tbl_programacion_usuario;
use App\Models\tbl_insp_cali;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * La programación como contenedor: la tabla de trabajo de cada usuario.
 *
 * Quien tiene permiso de ver todas las ve todas; el resto, sólo las suyas.
 * Una programación sin terminar es la que se retoma al volver a entrar.
 */
class ProgramacionUsuarioService
{
    /**
     * Programaciones terminadas que le tocan a este usuario, y la que tenga a medias.
     *
     * @return array{datos: Collection, enCurso: tbl_programacion_usuario|null}
     */
    public function listar(User $usuario): array
    {
        $terminadas = tbl_programacion_usuario::where('finished', 1)->with('usuario');

        if (! $usuario->hasPermissionTo('ver_programacion')) {
            $terminadas->where('id_usuario', $usuario->id);
        }

        return [
            'datos'   => $terminadas->get(),
            'enCurso' => $this->enCurso($usuario),
        ];
    }

    /** La programación que el usuario dejó a medias, si la hay. */
    public function enCurso(User $usuario): ?tbl_programacion_usuario
    {
        return tbl_programacion_usuario::where('finished', 0)
            ->where('id_usuario', $usuario->id)
            ->first();
    }

    /**
     * Abre una programación nueva para el usuario.
     *
     * Se nombra con la fecha del día, que es como las distingue quien las usa.
     */
    public function abrir(User $usuario): tbl_programacion_usuario
    {
        return DB::transaction(function () use ($usuario) {
            $programacion = new tbl_programacion_usuario();
            $programacion->nombre = 'Programación ' . Carbon::now()->format('Y-m-d');
            $programacion->id_usuario = $usuario->id;
            $programacion->save();

            return $programacion;
        });
    }

    /** Reabre una programación terminada para poder seguir editándola. */
    public function reabrir(tbl_programacion_usuario $programacion): void
    {
        DB::transaction(function () use ($programacion) {
            $programacion->finished = 0;
            $programacion->save();
        });
    }

    /** Los contratos de una programación. */
    public function contratos($id): Collection
    {
        return tbl_programacion_contrato::where('id_programacion', $id)->get();
    }

    /**
     * Borra la programación y todo lo que cuelga de ella.
     *
     * Va en una sola transacción: media programación borrada es peor que
     * ninguna.
     */
    public function eliminar($id): array
    {
        DB::transaction(function () use ($id) {
            tbl_programacion_contrato::where('id_programacion', $id)->get()->each->delete();
            tbl_programacion_usuario::find($id)?->delete();
        });

        return ['message' => 'Programación eliminada correctamente'];
    }

    /** Inspectores activos, como los espera el formulario. */
    public function tecnicosActivos(): Collection
    {
        return tbl_insp_cali::select('id', 'apellidos', 'nombres')
            ->where('state', 1)
            ->orderBy('apellidos')
            ->get();
    }
}
