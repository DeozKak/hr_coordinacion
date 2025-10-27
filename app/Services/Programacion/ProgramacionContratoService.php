<?php

namespace App\Services\Programacion;

use App\Jobs\CorreoProgramacion;
use App\Models\Programacion\tbl_programacion_base;
use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Programacion\tbl_programacion_usuario;
use App\Models\tbl_insp_cali;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgramacionContratoService
{
    private ExecutionVerifier $executionVerifier;

    public function __construct(ExecutionVerifier $executionVerifier)
    {

        $this->executionVerifier = $executionVerifier;
    }

    /**
     * Busca un contrato y devuelve los datos relacionados
     * Para generar el registro de programacion
     *
     * @param string $contrato
     * @return array|null
     */
    public function buscarContratoBase(string $contrato): ?array
    {
        // Validar si el contrato es numérico
        if (!is_numeric($contrato) || $contrato === '') {
            return null; // Retorna null si no pasa la validación.
        }

        // Buscar el contrato en la base de datos
        $datos = tbl_programacion_base::where('CONTRATO', $contrato)->first();

        if ($datos === null) {
            return null; // Si no se encuentra el contrato, retorna null.
        }

        // Validar el estado de recepción del contrato
        if ($datos?->ESTADO_RECEPCION !== null) {
            if ($datos->ESTADO_RECEPCION == '1' || $datos->ESTADO_RECEPCION == '2') {
                return ['error' => 'El contrato ya ha sido ejecutado'];
            }
        }

        // Obtener información del inspector relacionado (ID_TECNICO)
        if ($datos->ID_TECNICO) {
            $inspector = tbl_insp_cali::where('id', $datos->ID_TECNICO)->first();

            if ($inspector !== null) {
                // Concatenar el ID del inspector con nombre y apellido
                $datos->ID_TECNICO = $datos->ID_TECNICO . '. ' . $inspector->apellidos . ' ' . $inspector->nombres;
            } else {
                $datos->ID_TECNICO = null; // Si no hay resultado de inspector, dejar null.
            }
        }

        // Retornar los datos del contrato
        return $datos->toArray();
    }

    /**
     * Crea una nueva programacion para un contrato.
     *
     * @param array $data Datos enviados en la solicitud.
     * @return array Retorna éxito o error con la respuesta.
     */
    public function crearProgramacionContrato(array $data): array
    {

        try {
            // Validar si el contrato ya fue ejecutado.
            $executed = $this->executionVerifier->findExecuted($data['data'][1], $data['data'][2]);

            if ($executed) {
                if ($executed->TIPO_TRABAJO !== 'SA 12164') {
                    $fechaCompleta = $executed->FECHA;
                    $partes = explode(' ', $fechaCompleta);
                    $fecha = $partes[0];

                    $inspector = tbl_insp_cali::where('cedula', $executed->CC_OPERARIO)->first();

                    return [
                        'error' => true,
                        'movilidad' => 'Contrato ya ejecutado',
                        'usuario' => $inspector->apellidos . ' ' . $inspector->nombres,
                        'agendamiento' => $fecha,
                    ];
                }
            }

            if(in_array($data['data'][2],['10444','12161'])){
                // Validar si ya existe el contrato con los mismos datos.
                $exist = tbl_programacion_contrato::where('CONTRATO', $data['data'][1])
                    ->whereIn('TIPO_TRABAJO', ['10444','12161'])
                    ->first();
            }else{
                $exist = tbl_programacion_contrato::where('CONTRATO', $request->data[1])
                    ->where('ORDEN_TRABAJO', $request->data[6])
                    ->first();
            }



            if ($exist) {
                return [
                    'error' => true,
                    'exist' => 'Ya existe una programación con estos datos',
                    'id' => $exist->id,
                    'usuario' => $exist->PORQUE_PROGRAMO,
                    'agendamiento' => $exist->FECHA_AGENDAMIENTO,
                ];
            }

            // Crear el nuevo contrato de programación.
            DB::beginTransaction();

            $programacion = new tbl_programacion_contrato();
            $programacion->CONTRATO = $data['data'][1];
            $programacion->TIPO_TRABAJO = $data['data'][2];
            $programacion->FECHA = $data['data'][3];
            $programacion->CELULAR = $data['data'][4];
            $programacion->NOMBRE_USUARIO = $data['data'][5];
            $programacion->ORDEN_TRABAJO = $data['data'][6];
            $programacion->DIRECCION = $data['data'][7];
            $programacion->BARRIO = $data['data'][8];
            $programacion->CIUDAD = $data['data'][9];
            $programacion->ACTIVA = $data['data'][10];
            $programacion->SUSPENDIDO = $data['data'][11];
            $programacion->CATEGORIA = $data['data'][12];
            $programacion->FECHA_AGENDAMIENTO = $data['data'][13];
            $programacion->OBSERVACIONES = $data['data'][14];
            $programacion->PORQUE_PROGRAMO = $data['data'][15];
            $programacion->TECNICO = $data['data'][16];
            $programacion->HORA_INICIO = $data['data'][17];
            $programacion->HORA_FINAL = $data['data'][18];
            $programacion->id_programacion = $data['tabla'];
            $programacion->save();

            DB::commit();

            return [
                'error' => false,
                'message' => 'Registro guardado correctamente',
                'id' => $programacion->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return [
                'error' => true,
                'message' => 'Error al guardar en base de datos: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Actualiza una propiedad específica de un contrato de programación.
     *
     * @param int $id ID del contrato a actualizar.
     * @param string $campo Nombre de la propiedad a actualizar.
     * @param mixed $valor Nuevo valor para la propiedad.
     * @return array Respuesta indicando éxito o mensaje de error.
     */
    public function actualizarProgramacionContrato(int $id, string $campo, $valor): array
    {
        try {
            DB::beginTransaction();

            // Buscar el contrato por ID
            $programacion = tbl_programacion_contrato::find($id);

            if (!$programacion) {
                return [
                    'error' => true,
                    'message' => 'El contrato no existe.',
                ];
            }

            // Validar si la propiedad a actualizar es `FECHA_AGENDAMIENTO`
            if ($campo === 'FECHA_AGENDAMIENTO') {
                try {
                    $fecha = Carbon::createFromFormat('Y-m-d', $valor);

                    // Validación extra para rechazar fechas mal formateadas
                    if ($fecha->format('Y-m-d') !== $valor) {
                        return [
                            'error' => true,
                            'message' => 'La fecha debe tener el formato correcto (Y-m-d).',
                        ];
                    }
                } catch (\Exception $e) {
                    return [
                        'error' => true,
                        'message' => 'La fecha debe tener el formato correcto (Y-m-d).',
                    ];
                }
            }

            // Actualizar el campo
            $programacion->$campo = $valor;
            $programacion->save();

            DB::commit();

            return [
                'error' => false,
                'message' => 'Registro actualizado correctamente.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return [
                'error' => true,
                'message' => 'Error al actualizar registro: ' . $e->getMessage(),
            ];
        }

    }


    /**
     * Elimina una programacion con un contrato especifico.
     *
     * @param int $id ID del contrato a eliminar.
     * @return array Respuesta indicando éxito o error.
     */
    public function eliminarProgramacionContrato(int $id): array
    {
        try {
            DB::beginTransaction();

            // Buscar el contrato en la base de datos
            $programacion = tbl_programacion_contrato::find($id);

            // Verificar si el contrato existe
            if (!$programacion) {
                return [
                    'error' => true,
                    'message' => 'El contrato no existe.',
                ];
            }

            // Eliminar el contrato
            $programacion->delete();

            DB::commit();

            return [
                'error' => false,
                'message' => 'Registro eliminado correctamente.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar registro. ' . $e->getMessage());

            return [
                'error' => true,
                'message' => 'Error al eliminar registro. ' . $e->getMessage(),
            ];
        }
    }

    public function finalizarProgramacion(int $id): array
    {
        try {
            DB::beginTransaction();

            // Buscar la programación por ID
            $programacion = tbl_programacion_usuario::find($id);

            if (!$programacion) {
                return [
                    'error' => true,
                    'message' => 'La programación no existe.',
                ];
            }

            // Marcar la programación como terminada
            $programacion->finished = 1;
            $programacion->save();

            $user = User::find($programacion->id_usuario);

            // Obtener todos los contratos asociados a la programación
            $programadas = tbl_programacion_contrato::where('id_programacion', $id)->get();

            // Verificar si la programación requiere un mensaje de correo programado
            if ($programacion->mensaje == 0) {
                $programacion->mensaje = 1;
                $programacion->save();

                // Despachar el correo
                CorreoProgramacion::dispatch($user, $id);
            }

            // Procesar cada contrato asociado
            foreach ($programadas as $programada) {
                if (empty($programada->CELULAR) || $programada->mensaje == 1) {
                    continue; // Ignorar contratos que ya tienen mensaje o no tienen celular.
                }

                // Calcular el saludo basado en la hora actual
                date_default_timezone_set('America/Bogota');
                $horaActual = date('H');
                if ($horaActual >= 5 && $horaActual < 12) {
                    $saludo = "Buenos días";
                } elseif ($horaActual >= 12 && $horaActual < 19) {
                    $saludo = "Buenas tardes";
                } else {
                    $saludo = "Buenas noches";
                }

                try {
                    // Validar y formatear la fecha
                    $fecha_carbon = Carbon::createFromFormat('Y-m-d', $programada->FECHA_AGENDAMIENTO);
                    if ($fecha_carbon->format('Y-m-d') !== $programada->FECHA_AGENDAMIENTO) {
                        return [
                            'error' => true,
                            'message' => 'La fecha debe tener el formato correcto (Y-m-d).',
                        ];
                    }

                    // Formatear fecha al formato deseado (en español)
                    $fecha_formateada = $fecha_carbon->locale('es')->isoFormat('D [de] MMMM [de] YYYY');

                    // Construir el mensaje (descoméntalo si necesitas enviar peticiones HTTP aquí)
                    /*
                    $tecnico_sin_numero = substr($programada->TECNICO, strpos($programada->TECNICO, ". ") + 2);
                    $bodyData = [
                        'typing_time' => 0,
                        'to' => '57' . $programada->CELULAR,
                        'body' => $saludo . ', Sr./Sra. ' . $programada->NOMBRE_USUARIO . '. 👋' .
                            'Le informamos que la inspección de la red de gas en su predio está programada para el día ' . $fecha_formateada . ' entre las ' .
                            $programada->HORA_INICIO . ' y ' . $programada->HORA_FINAL . '. La persona encargada será ' .
                            $tecnico_sin_numero . '. 👷‍♂️ Agradecemos su atención y colaboración. 🙏',
                    ];
                    */

                } catch (\Exception $e) {
                    return [
                        'error' => true,
                        'message' => 'La fecha debe tener el formato correcto (Y-m-d).',
                    ];
                }

                // Marcar el contrato como notificado
                $programada->mensaje = 1;
                $programada->save();
            }

            DB::commit();
            session()->flash('success', 'Programación finalizada correctamente');
            return [
                'error' => false,
                'message' => 'Programación finalizada correctamente.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al finalizar programación: ' . $e->getMessage());

            return [
                'error' => true,
                'message' => 'Error al finalizar programación: ' . $e->getMessage(),
            ];
        }
    }
}
