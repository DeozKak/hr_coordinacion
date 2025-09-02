<?php

namespace App\Http\Controllers;


use App\Models\Bitacoras\tbl_bitacora_contrato;
use App\Models\ControlStickers\tbl_controlstick_historico;
use App\Models\ControlStickers\tbl_controlstick_semana;
use App\Models\tbl_insp_cali;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Stickers\tbl_sticker_tipo;
use App\Models\Stickers\tbl_sticker_inventario;
use App\Models\Stickers\tbl_inspector_sticker;
use App\Models\Stickers\tbl_asignacion_sticker_historial;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class StickersController extends Controller
{
    /**
     *
     * Funcion retorna vista con las variables de Stickers y los inspectores activos
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): \Illuminate\Contracts\View\View
    {
        $Stickers = tbl_sticker_tipo::with('Inventario')->OrderBy('nombre')->get();
        // dd($Stickers);
        // Consulta a inspectores activos y la última fecha de asignación por tipo de sticker
        $inspectores = tbl_insp_cali::where('state', 1)
            ->selectRaw('id, CONCAT(apellidos, " ", nombres) as nombre_completo')
            ->with('Stickers') // Relación con todos los stickers asignados a cada inspector
            ->with(['HistoricoStickers' => function ($q) {
                $q->select('id', 'id_inspector', 'id_sticker_tipo', 'fecha_asignacion', 'cantidad') // Selección optimizada
                ->whereIn('id', function ($sub) {
                    $sub->selectRaw('MAX(id)') // Toma el último registro por inspector y tipo de sticker
                    ->from('tbl_asignacion_sticker_historial')
                        ->groupBy('id_inspector', 'id_sticker_tipo');
                });
            }])
            ->orderBy('nombre_completo', 'asc') // Ordena los inspectores por nombre completo
            ->get();

        return view('stickers.index', compact('inspectores', 'Stickers'));
    }

    /**
     *
     * Funcion para Obtener inventario total de cada tipo de Sticker
     *
     * @return JsonResponse
     */
    public function getInventario(): \Illuminate\Http\JsonResponse
    {
        $Stickers = tbl_sticker_tipo::with('Inventario')->OrderBy('nombre')->get();
        // Devuelve solo los datos necesarios para JS
        $data = [];
        foreach ($Stickers as $sticker) {
            $data[] = [
                'id' => $sticker->id,
                'nombre' => $sticker->nombre,
                'inventario' => $sticker->Inventario->cantidad_disponible ?? 0,
            ];
        }
        return response()->json($data);
    }

    /**
     * funcion dedicada a actualizar el inventario total de Stickers para cada uno
     *
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActualizarInventario($id, Request $request): \Illuminate\Http\JsonResponse
    {
        //Validación de entradas de usuario
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|numeric',
        ], [
            'cantidad.required' => 'la cantidad es requerida',
            'cantidad.numeric' => 'Se tienen que ingresar numeros'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        //guardar entrada a variable local
        $cantidad = $request->cantidad;
        //Actualizar valor ingresado a BD de inventario
        try {
            DB::beginTransaction();
            $tipo = tbl_sticker_inventario::where('id_sticker_tipo', $id)->first();
            $tipo->cantidad_disponible = $tipo->cantidad_disponible + $cantidad;
            $tipo->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            log::error($e->getMessage());
            return response()->json(['error' => 'No se pudo actualizar el inventario ' . $e->getMessage()], 500);
        }
        return response()->json(['success' => 'Se actualizo el inventario correctamente',
            'value' => $tipo->cantidad_disponible], 200);

    }

    /**
     * Función dedicada a recibir entradas de usuario para la asignación de uno o diferentes
     * tipos de Sticker a un inspector y registrar en BD en las tablas correspondientes,
     * además de guardar un histórico de lo asignado
     * @param Request $request el id del inspector al cual se van a asignar la cantidad de Stickers
     * @param array $stickers  con los tipos de stickers y cantidad a asignar
     *
     * @return JsonResponse
     *
     * */
    public function asignar(Request $request): \Illuminate\Http\JsonResponse
    {
        //Validación de entrada de usuario
        $validator = Validator::make($request->all(), [
            'idInspector' => 'required',
            'stickers' => 'required|array',
            'stickers.*' => 'required|numeric', // <--- Valida que cada elemento sea numérico
        ],
            [
                'idInspector.required' => 'el id de inspector es requerido',
                'stickers.required' => 'los stickers son requeridos',
                'stickers.*.required' => 'el id de sticker es requerido',
                'stickers.*.numeric' => 'Se tienen que ingresar numeros'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        //las entradas se asignan a variables locales
        //id del inspector
        $id_inspector = $request->idInspector;
        // array con id_sticker_tipo => cantidad
        $stickers = $request->stickers;

        //inicio de inserción a BD
        try {
            DB::beginTransaction();
            // se itera el array de Stickers, el id de Sticker es la llave del array
            foreach ($stickers as $id_sticker_tipo => $cantidad) {

                // Buscar si el registro ya existe
                $registro = tbl_inspector_sticker::where('id_inspector', $id_inspector)
                    ->where('id_sticker_tipo', $id_sticker_tipo)
                    ->first();


                if ($registro) {
                    // Si existe, actualiza la cantidad
                    $registro->cantidad_asignada = $registro->cantidad_asignada + $cantidad;
                    $registro->save();
                    // se resta de inventario la cantidad ingresada
                    $inventario = tbl_sticker_inventario::where('id_sticker_tipo', $id_sticker_tipo)->first();
                    $inventario->cantidad_disponible = $inventario->cantidad_disponible - $cantidad;
                    $inventario->save();
                    // se crea un registro de historial de lo asignado
                    tbl_asignacion_sticker_historial::create([
                        'id_inspector' => $id_inspector,
                        'id_sticker_tipo' => $id_sticker_tipo,
                        'cantidad' => $cantidad,
                        'fecha_asignacion' => date('Y-m-d H:i:s'),
                        'id_usuario_asigna' => auth()->user()->id
                    ]);

                } else {
                    // Si no existe, crea el registro
                    tbl_inspector_sticker::create([
                        'id_inspector' => $id_inspector,
                        'id_sticker_tipo' => $id_sticker_tipo,
                        'cantidad_asignada' => $cantidad,
                    ]);
                    // se crea un registro de historial de lo asignado
                    tbl_asignacion_sticker_historial::create([
                        'id_inspector' => $id_inspector,
                        'id_sticker_tipo' => $id_sticker_tipo,
                        'cantidad' => $cantidad,
                        'fecha_asignacion' => date('Y-m-d H:i:s'),
                        'id_usuario_asigna' => auth()->user()->id
                    ]);
                    // se resta de inventario la cantidad ingresada
                    $inventario = tbl_sticker_inventario::where('id_sticker_tipo', $id_sticker_tipo)->first();
                    $inventario->cantidad_disponible = $inventario->cantidad_disponible - $cantidad;
                    $inventario->save();

                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            log::error($e->getMessage());
            return response()->json(['error' => 'No se pudo Asignar' . $e->getMessage()], 500);
        }
        return response()->json(['success' => 'Stickers asignados correctamente'], 200);
    }

    /**
     * Obtiene los stickers asignados a un inspector específico
     *
     * @param int $idInspector
     * @return JsonResponse
     */

    public function getStickersAsignados($idInspector): JsonResponse
    {
        try {
            $stickersAsignados = tbl_inspector_sticker::where('id_inspector', $idInspector)
                ->get();

            return response()->json($stickersAsignados, 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener stickers asignados: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener stickers asignados ' . $e->getMessage()], 500);
        }
    }

    /**
     * Función dedicada a desasignar stickers de un inspector y devolverlos al inventario
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function desasignar(Request $request): JsonResponse
    {
        // Validación de entrada de usuario
        $validator = Validator::make($request->all(), [
            'idInspector' => 'required|integer',
            'stickers' => 'required|array',
            'stickers.*' => 'required|numeric|min:1',
        ], [
            'idInspector.required' => 'El id de inspector es requerido',
            'idInspector.integer' => 'El id de inspector debe ser un número',
            'stickers.required' => 'Los stickers son requeridos',
            'stickers.*.required' => 'La cantidad de sticker es requerida',
            'stickers.*.numeric' => 'Se tienen que ingresar números',
            'stickers.*.min' => 'La cantidad debe ser mayor a 0'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $id_inspector = $request->idInspector;
        $stickers = $request->stickers;

        try {
            DB::beginTransaction();

            foreach ($stickers as $id_sticker_tipo => $cantidad) {
                // Buscar el registro del inspector
                $registro = tbl_inspector_sticker::where('id_inspector', $id_inspector)
                    ->where('id_sticker_tipo', $id_sticker_tipo)
                    ->first();

                if (!$registro) {
                    throw new \Exception("No se encontró asignación de sticker tipo {$id_sticker_tipo} para este inspector");
                }

                // Validar que no se desasigne más de lo asignado
                if ($cantidad > $registro->cantidad_asignada) {
                    throw new \Exception("No se puede desasignar más stickers de los asignados. Cantidad asignada: {$registro->cantidad_asignada}");
                }

                // Actualizar la cantidad asignada
                $registro->cantidad_asignada = $registro->cantidad_asignada - $cantidad;

                // Si la cantidad queda en 0, eliminar el registro
                if ($registro->cantidad_asignada == 0) {
                    $registro->delete();
                } else {
                    $registro->save();
                }

                // Devolver al inventario (sumar la cantidad)
                $inventario = tbl_sticker_inventario::where('id_sticker_tipo', $id_sticker_tipo)->first();
                if (!$inventario) {
                    throw new \Exception("No se encontró inventario para el sticker tipo {$id_sticker_tipo}");
                }

                $inventario->cantidad_disponible = $inventario->cantidad_disponible + $cantidad;
                $inventario->save();

                // Crear registro histórico con cantidad negativa para indicar desasignación
                tbl_asignacion_sticker_historial::create([
                    'id_inspector' => $id_inspector,
                    'id_sticker_tipo' => $id_sticker_tipo,
                    'cantidad' => -$cantidad, // Cantidad negativa para distinguir desasignación
                    'fecha_asignacion' => now(),
                    'id_usuario_asigna' => auth()->user()->id
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al desasignar stickers: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudieron desasignar los stickers: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Stickers desasignados correctamente'], 200);
    }


}
