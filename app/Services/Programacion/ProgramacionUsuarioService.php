<?php

namespace App\Services\Programacion;

use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Programacion\tbl_programacion_usuario;
use App\Models\tbl_insp_cali;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgramacionUsuarioService
{

    /**
     * Obtiene las programaciones para el usuario actual.
     *
     * @param User $user Usuario autenticado
     * @return array Retorna un arreglo con las programaciones terminadas y en curso.
     */
    public function obtenerProgramacionesUsuario(User $user): array
    {
        // Si el usuario tiene el permiso 'ver_programacion'
        if ($user->hasPermissionTo('ver_programacion')) {
            // Trae todas las programaciones terminadas
            $programacionesTerminadas = tbl_programacion_usuario::where('finished', 1)->with('usuario')->get();
            // Trae la primera programación no terminada
            $programacionEnCurso = tbl_programacion_usuario::where('finished', 0)->where('id_usuario', $user->id)->first();
        }else {
            // Solo trae sus propias programaciones terminadas
            $programacionesTerminadas = tbl_programacion_usuario::where('finished', 1)->where('id_usuario', $user->id)->with('usuario')->get();

            // Trae la primera programación no terminada asociada al usuario
            $programacionEnCurso = tbl_programacion_usuario::where('finished', 0)->where('id_usuario', $user->id)->first();
        }
        // Retorna ambas consultas en un arreglo
        return [
            'terminadas' => $programacionesTerminadas,
            'enCurso' => $programacionEnCurso,
        ];

    }
    /**
     * Crear o recuperar una programación para el usuario actual
     *
     * @param User $user
     * @return array
     */
    public function crearNuevaProgramacion(User $user): array
    {
        // Verifica si ya existe una programación activa para el usuario
        $programacion = tbl_programacion_usuario::where('finished', 0)
            ->where('id_usuario', $user->id)
            ->first();

        if (is_null($programacion)) {
            try {
                // Inicia una transacción para la creación de una nueva programación
                DB::beginTransaction();

                $fechaActual = Carbon::now();
                $soloFecha = $fechaActual->format('Y-m-d');

                $programacion = new tbl_programacion_usuario;
                $programacion->nombre = "Programación " . $soloFecha;
                $programacion->id_usuario = $user->id;
                $programacion->save();

                // Consulta los técnicos activos ordenados por apellido
                $tecnicos = tbl_insp_cali::select('id', 'apellidos', 'nombres')
                    ->where('state', 1)
                    ->orderBy('apellidos') // Ordenar por apellidos
                    ->get();

                DB::commit();

                // Retorna los datos necesarios para la vista
                return [
                    'tecnicos' => $tecnicos,
                    'user' => $user,
                    'programacion' => $programacion,
                ];
            } catch (\Exception $e) {
                // En caso de error, realiza rollback y registra el error
                Log::error($e);
                DB::rollback();

                // Retorna un mensaje de error
                return [
                    'error' => 'Ocurrió un error al crear la tabla: ' . $e->getMessage(),
                ];
            }
        }

        // Si ya existe una programación activa, también se retorna
        return [
            'programacion' => $programacion,
            'redirect' => true, // Indica que debe redirigirse al index
        ];
    }

    /**
     * Maneja la lógica del método show en el servicio
     *
     * @param int $id ID de la programación
     * @param string|null $action Acción que se quiere realizar (edit, view)
     * @return array Datos necesarios para la vista
     */
    public function obtenerDetalleProgramacion(int $id, ?string $action): array
    {
        $programacion = tbl_programacion_usuario::findOrFail($id); // Encuentra o lanza excepción
        $tabla = tbl_programacion_contrato::where('id_programacion', $id)->get();

        // Si la acción es editar, verifica permisos y actualiza el estado
        if ($action === 'edit') {
            if (Auth::user()->hasPermissionTo('generar_programacion')) {
                try {
                    DB::beginTransaction();
                    $programacion->finished = 0;
                    $programacion->save();
                    DB::commit();
                } catch (\Exception $e) {
                    Log::error($e);
                    DB::rollBack();
                    return ['error' => 'Ocurrió un error al cargar la tabla: ' . $e->getMessage()];
                }
            } else {
                return ['error' => 'Acción no autorizada.'];
            }
        }

        // Usuarios relacionados con la programación
        $user = User::find($programacion->id_usuario);
        $tecnicos = tbl_insp_cali::select('id', 'apellidos', 'nombres')
            ->where('state', 1)
            ->orderBy('apellidos') // Ordenar por apellidos ascendente
            ->get();

        return [
            'tecnicos' => $tecnicos,
            'user' => $user,
            'programacion' => $programacion,
            'tabla' => $tabla,
            'action' => $action,
        ];
    }

    /**
     * Elimina una programación y sus contratos relacionados.
     *
     * @param int $id ID de la programación a eliminar.
     * @return array Respuesta indicando éxito o error.
     */
    public function eliminarProgramacion(int $id): array
    {
        try {
            DB::beginTransaction();

            // Obtiene la programación por ID
            $programacion = tbl_programacion_usuario::find($id);

            if (!$programacion) {
                return ['error' => 'La programación no existe.'];
            }

            // Elimina los contratos relacionados con la programación
            $contratos = tbl_programacion_contrato::where('id_programacion', $id)->get();
            $contratos->each->delete();

            // Elimina la programación
            $programacion->delete();

            DB::commit();

            return ['message' => 'Programación eliminada correctamente.'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return ['error' => 'Error al eliminar Programación: ' . $e->getMessage()];
        }
    }
}
