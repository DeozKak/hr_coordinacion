<?php

namespace App\Http\Controllers;


use App\Models\TblInspCali;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stickers\TblStickerTipo;
use App\Models\Stickers\TblStickerInventario;
use App\Models\Stickers\TblInspectorSticker;
use App\Models\Stickers\TblAsignacionStickerHistorial;
use App\Models\Stickers\TblStickerActaSerial;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class StickersController extends Controller
{
    private $idStickerActa;


    public function __construct()
    {
        // Esto busca el ID del sticker "ACTA".
        // Si el nombre cambia, solo debes ajustarlo aquí.
        $stickerActa = TblStickerTipo::where('nombre', 'ACTAS')->first();
        $this->idStickerActa = $stickerActa ? $stickerActa->id : null;
    }
    /**
     *
     * Funcion retorna vista con las variables de stickers y los inspectores activos
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): \Illuminate\Contracts\View\View
    {
        $Stickers = TblStickerTipo::with('Inventario')->OrderBy('nombre')->get();
        // dd($stickers);
        // Consulta a inspectores activos y la última fecha de asignación por tipo de sticker
        $inspectores = TblInspCali::where('state', 1)
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
        $Stickers = TblStickerTipo::with('Inventario')->OrderBy('nombre')->get();
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
     * funcion dedicada a actualizar el inventario total de stickers para cada uno
     *
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActualizarInventario($id, Request $request): \Illuminate\Http\JsonResponse
    {
// --- INICIO DE LÓGICA PARA ACTAS CON SERIALES ---
        if ($id == $this->idStickerActa) {

            $validator = Validator::make($request->all(), [
                'serial_inicio' => 'required|numeric',
                'serial_fin' => 'required|numeric|gte:serial_inicio', // gte = mayor o igual que serial_inicio
            ], [
                'serial_inicio.required' => 'El serial inicial es requerido.',
                'serial_fin.required' => 'El serial final es requerido.',
                'serial_fin.gte' => 'El serial final debe ser mayor o igual al inicial.'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $inicio = $request->serial_inicio;
            $fin = $request->serial_fin;
            $serialesNuevos = [];
            $serialesExistentes = [];

            // 1. Verificar cuáles seriales ya existen
            for ($i = $inicio; $i <= $fin; $i++) {
                $existe = TblStickerActaSerial::where('serial', $i)->where('id_sticker_tipo', $id)->exists();
                if ($existe) {
                    $serialesExistentes[] = $i;
                } else {
                    $serialesNuevos[] = $i;
                }
            }

            if (count($serialesExistentes) > 0) {
                return response()->json(['error' => 'Los siguientes seriales ya existen: ' . implode(', ', $serialesExistentes)], 409); // 409 Conflict
            }

            // 2. Insertar los nuevos seriales
            try {
                DB::beginTransaction();
                $datosInsertar = [];
                foreach ($serialesNuevos as $serial) {
                    $datosInsertar[] = [
                        'id_sticker_tipo' => $id,
                        'serial' => $serial,
                        'estado' => 'en_inventario',
                        'id_inspector' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                TblStickerActaSerial::insert($datosInsertar);

                // 3. Sincronizar el inventario total (para compatibilidad con la vista)
                $inventarioTotalActas = TblStickerActaSerial::where('id_sticker_tipo', $id)
                    ->where('estado', 'en_inventario')
                    ->count();

                $inventario = TblStickerInventario::firstOrCreate(['id_sticker_tipo' => $id]);
                $inventario->cantidad_disponible = $inventarioTotalActas;
                $inventario->save();

                DB::commit();

                return response()->json([
                    'success' => 'Se agregaron ' . count($serialesNuevos) . ' seriales de Actas al inventario.',
                    'value' => $inventario->cantidad_disponible
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al agregar seriales de Actas: ' . $e->getMessage());
                return response()->json(['error' => 'No se pudo actualizar el inventario ' . $e->getMessage()], 500);
            }

        }
        // --- FIN DE LÓGICA PARA ACTAS ---

        // --- LÓGICA ORIGINAL (para otros stickers) ---
        else {
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
                $tipo = TblStickerInventario::where('id_sticker_tipo', $id)->first();
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

    }

    /**
     * Función dedicada a recibir entradas de usuario para la asignación de uno o diferentes
     * tipos de Sticker a un inspector y registrar en BD en las tablas correspondientes,
     * además de guardar un histórico de lo asignado
     * @param Request $request el id del inspector al cual se van a asignar la cantidad de stickers
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
            'stickers' => 'required_without:seriales_acta|array', // Obligatorio si no se incluye seriales_acta
            'stickers.*' => 'required|numeric',
            'seriales_acta' => 'nullable|required_without:stickers|array', // Obligatorio si no se incluye stickers
            'seriales_acta.serial_inicio' => 'required_with:seriales_acta|numeric',
            'seriales_acta.serial_fin' => 'required_with:seriales_acta|numeric|gte:seriales_acta.serial_inicio',
        ], [
            'idInspector.required' => 'El ID del inspector es requerido.',
            'stickers.required_without' => 'Los stickers son requeridos si no se proporcionan los seriales de ACTA.',
            'stickers.*.required' => 'Se requiere un ID válido para cada sticker.',
            'stickers.*.numeric' => 'Los IDs de los stickers deben ser valores numéricos.',
            'seriales_acta.required_without' => 'Los seriales de ACTA son requeridos si no se proporcionan stickers.',
            'seriales_acta.serial_inicio.required_with' => 'El serial inicial es requerido cuando los seriales de ACTA están presentes.',
            'seriales_acta.serial_fin.required_with' => 'El serial final es requerido cuando los seriales de ACTA están presentes.',
            'seriales_acta.serial_fin.gte' => 'El serial final debe ser mayor o igual al serial inicial.',
        ]);


        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        //las entradas se asignan a variables locales
        //id del inspector
        $id_inspector = $request->idInspector;
        // array con id_sticker_tipo => cantidad
        $stickers_cuantitativos = $request->stickers ?? [];
        $seriales_acta_rango = $request->seriales_acta ?? null;
        //inicio de inserción a BD
        try {
            DB::beginTransaction();
            // --- 1. PROCESAR STICKERS CUANTITATIVOS (los que no son ACTA) ---
            foreach ($stickers_cuantitativos as $id_sticker_tipo => $cantidad) {

                // Nos aseguramos de no procesar ACTA aquí por error
                if ($id_sticker_tipo == $this->idStickerActa) continue;

                // Buscar si el registro ya existe
                $registro = TblInspectorSticker::where('id_inspector', $id_inspector)
                    ->where('id_sticker_tipo', $id_sticker_tipo)
                    ->first();

                // Validar inventario
                $inventario = TblStickerInventario::where('id_sticker_tipo', $id_sticker_tipo)->first();
                if (!$inventario || $inventario->cantidad_disponible < $cantidad) {
                    throw new \Exception("Inventario insuficiente para el sticker tipo ID: {$id_sticker_tipo}");
                }

                if ($registro) {
                    $registro->cantidad_asignada = $registro->cantidad_asignada + $cantidad;
                    $registro->save();
                } else {
                    TblInspectorSticker::create([
                        'id_inspector' => $id_inspector,
                        'id_sticker_tipo' => $id_sticker_tipo,
                        'cantidad_asignada' => $cantidad,
                    ]);
                }

                // se resta de inventario la cantidad ingresada
                $inventario->cantidad_disponible = $inventario->cantidad_disponible - $cantidad;
                $inventario->save();

                // se crea un registro de historial de lo asignado
                TblAsignacionStickerHistorial::create([
                    'id_inspector' => $id_inspector,
                    'id_sticker_tipo' => $id_sticker_tipo,
                    'cantidad' => $cantidad,
                    'fecha_asignacion' => now(),
                    'id_usuario_asigna' => auth()->user()->id
                ]);
            }
            // --- 2. PROCESAR STICKERS SERIALIZADOS (ACTAS) ---
            if ($seriales_acta_rango && $this->idStickerActa) {
                $inicio = $seriales_acta_rango['serial_inicio'];
                $fin = $seriales_acta_rango['serial_fin'];
                $cantidad_actas = ($fin - $inicio) + 1;
                $serialesParaAsignar = [];

                // Validar disponibilidad de seriales
                $serialesNoDisponibles = [];
                for ($i = $inicio; $i <= $fin; $i++) {
                    $serial = TblStickerActaSerial::where('serial', $i)
                        ->where('id_sticker_tipo', $this->idStickerActa)
                        ->first();

                    if (!$serial || $serial->estado != 'en_inventario') {
                        $serialesNoDisponibles[] = $i;
                    } else {
                        $serialesParaAsignar[] = $serial->id; // Guardamos el ID del registro de serial
                    }
                }

                if (count($serialesNoDisponibles) > 0) {
                    throw new \Exception("Los siguientes seriales de ACTA no están disponibles en inventario: " . implode(', ', $serialesNoDisponibles));
                }

                // Asignar seriales al inspector
                TblStickerActaSerial::whereIn('id', $serialesParaAsignar)->update([
                    'estado' => 'asignado',
                    'id_inspector' => $id_inspector
                ]);

                // Sincronizar tabla de totales del inspector (para compatibilidad)
                $totalActasAsignadasInspector = TblStickerActaSerial::where('id_inspector', $id_inspector)
                    ->where('id_sticker_tipo', $this->idStickerActa)
                    ->where('estado', 'asignado')
                    ->count();

                $registroInspector = TblInspectorSticker::firstOrCreate(
                    ['id_inspector' => $id_inspector, 'id_sticker_tipo' => $this->idStickerActa],
                    ['cantidad_asignada' => 0]
                );
                $registroInspector->cantidad_asignada = $totalActasAsignadasInspector;
                $registroInspector->save();

                // Sincronizar inventario general (para compatibilidad)
                $totalActasEnInventario = TblStickerActaSerial::where('id_sticker_tipo', $this->idStickerActa)
                    ->where('estado', 'en_inventario')
                    ->count();

                $inventarioActas = TblStickerInventario::where('id_sticker_tipo', $this->idStickerActa)->first();
                $inventarioActas->cantidad_disponible = $totalActasEnInventario;
                $inventarioActas->save();

                // Guardar historial (guardamos la cantidad y un detalle de los rangos)
                TblAsignacionStickerHistorial::create([
                    'id_inspector' => $id_inspector,
                    'id_sticker_tipo' => $this->idStickerActa,
                    'cantidad' => $cantidad_actas, // Cantidad total
                    'fecha_asignacion' => now(),
                    'id_usuario_asigna' => auth()->user()->id,
                    'detalle_seriales' => "Asignados: {$inicio} al {$fin}" // Columna opcional (ver nota abajo)
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            log::error($e->getMessage());
            return response()->json(['error' => 'No se pudo Asignar' . $e->getMessage()], 500);
        }
        return response()->json(['success' => 'stickers asignados correctamente'], 200);
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
            $stickersAsignados = TblInspectorSticker::where('id_inspector', $idInspector)
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
            'stickers' => 'nullable|array', // stickers cuantitativos
            'stickers.*' => 'required|numeric|min:1',
            'seriales_acta' => 'nullable|array', // stickers serializados (ACTA)
            'seriales_acta.serial_inicio' => 'required_with:seriales_acta|numeric',
            'seriales_acta.serial_fin' => 'required_with:seriales_acta|numeric|gte:seriales_acta.serial_inicio',
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
        $stickers_cuantitativos = $request->stickers ?? [];
        $seriales_acta_rango = $request->seriales_acta ?? null;

        try {
            DB::beginTransaction();

            // --- 1. PROCESAR STICKERS CUANTITATIVOS (los que no son ACTA) ---
            foreach ($stickers_cuantitativos as $id_sticker_tipo => $cantidad) {

                if ($id_sticker_tipo == $this->idStickerActa) continue;

                $registro = TblInspectorSticker::where('id_inspector', $id_inspector)
                    ->where('id_sticker_tipo', $id_sticker_tipo)
                    ->first();

                if (!$registro || $cantidad > $registro->cantidad_asignada) {
                    throw new \Exception("No se puede desasignar más stickers (ID: {$id_sticker_tipo}) de los asignados.");
                }

                $registro->cantidad_asignada = $registro->cantidad_asignada - $cantidad;

                if ($registro->cantidad_asignada == 0) {
                    $registro->delete();
                } else {
                    $registro->save();
                }

                // Devolver al inventario (sumar la cantidad)
                $inventario = TblStickerInventario::where('id_sticker_tipo', $id_sticker_tipo)->first();
                $inventario->cantidad_disponible = $inventario->cantidad_disponible + $cantidad;
                $inventario->save();

                // Crear registro histórico con cantidad negativa
                TblAsignacionStickerHistorial::create([
                    'id_inspector' => $id_inspector,
                    'id_sticker_tipo' => $id_sticker_tipo,
                    'cantidad' => -$cantidad,
                    'fecha_asignacion' => now(),
                    'id_usuario_asigna' => auth()->user()->id
                ]);
            }
            // --- 2. PROCESAR STICKERS SERIALIZADOS (ACTAS) ---
            if ($seriales_acta_rango && $this->idStickerActa) {
                $inicio = $seriales_acta_rango['serial_inicio'];
                $fin = $seriales_acta_rango['serial_fin'];
                $cantidad_actas = ($fin - $inicio) + 1;
                $serialesParaDesasignar = [];

                // Validar que el inspector POSEE esos seriales
                $serialesNoPertenecen = [];
                for ($i = $inicio; $i <= $fin; $i++) {
                    $serial = TblStickerActaSerial::where('serial', $i)
                        ->where('id_sticker_tipo', $this->idStickerActa)
                        ->where('id_inspector', $id_inspector)
                        ->where('estado', 'asignado')
                        ->first();

                    if (!$serial) {
                        $serialesNoPertenecen[] = $i;
                    } else {
                        $serialesParaDesasignar[] = $serial->id;
                    }
                }

                if (count($serialesNoPertenecen) > 0) {
                    throw new \Exception("Los siguientes seriales de ACTA no pertenecen al inspector o no están asignados: " . implode(', ', $serialesNoPertenecen));
                }

                // Desasignar seriales (devolver a inventario)
                TblStickerActaSerial::whereIn('id', $serialesParaDesasignar)->update([
                    'estado' => 'en_inventario',
                    'id_inspector' => null
                ]);

                // Sincronizar tabla de totales del inspector
                $totalActasAsignadasInspector = TblStickerActaSerial::where('id_inspector', $id_inspector)
                    ->where('id_sticker_tipo', $this->idStickerActa)
                    ->where('estado', 'asignado')
                    ->count();

                $registroInspector = TblInspectorSticker::where('id_inspector', $id_inspector)
                    ->where('id_sticker_tipo', $this->idStickerActa)
                    ->first();

                if ($registroInspector) {
                    if ($totalActasAsignadasInspector == 0) {
                        $registroInspector->delete();
                    } else {
                        $registroInspector->cantidad_asignada = $totalActasAsignadasInspector;
                        $registroInspector->save();
                    }
                }

                // Sincronizar inventario general
                $totalActasEnInventario = TblStickerActaSerial::where('id_sticker_tipo', $this->idStickerActa)
                    ->where('estado', 'en_inventario')
                    ->count();

                $inventarioActas = TblStickerInventario::where('id_sticker_tipo', $this->idStickerActa)->first();
                $inventarioActas->cantidad_disponible = $totalActasEnInventario;
                $inventarioActas->save();

                // Guardar historial
                TblAsignacionStickerHistorial::create([
                    'id_inspector' => $id_inspector,
                    'id_sticker_tipo' => $this->idStickerActa,
                    'cantidad' => -$cantidad_actas, // Negativo para indicar desasignación
                    'fecha_asignacion' => now(),
                    'id_usuario_asigna' => auth()->user()->id,
                    'detalle_seriales' => "Devueltos: {$inicio} al {$fin}" // Opcional (ver nota arriba)
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al desasignar stickers: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudieron desasignar los stickers: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => 'stickers desasignados correctamente'], 200);
    }

    /**
     * Obtiene los seriales de ACTAS que están en inventario y los agrupa en rangos.
     *
     * @return JsonResponse
     */
    public function getSerialesInventarioActas(): JsonResponse
    {
        if (!$this->idStickerActa) {
            return response()->json(['error' => 'Sticker "ACTA" no configurado.'], 404);
        }

        try {
            // 1. Obtener todos los seriales en inventario, ordenados
            $seriales = TblStickerActaSerial::where('id_sticker_tipo', $this->idStickerActa)
                ->where('estado', 'en_inventario')
                ->orderBy('serial', 'asc')
                ->pluck('serial') // Solo nos interesa el número de serial
                ->toArray();

            // 2. Agrupar en rangos
            $rangos = [];
            if (count($seriales) > 0) {
                $rangoInicio = $seriales[0];
                $rangoPrevio = $seriales[0];

                for ($i = 1; $i < count($seriales); $i++) {
                    $actual = $seriales[$i];

                    // Si el serial actual no es consecutivo al anterior
                    if ($actual != $rangoPrevio + 1) {
                        // Cerramos el rango anterior
                        if ($rangoInicio == $rangoPrevio) {
                            $rangos[] = (string)$rangoInicio; // Rango de uno solo
                        } else {
                            $rangos[] = $rangoInicio . ' - ' . $rangoPrevio; // Rango múltiple
                        }
                        // Empezamos un nuevo rango
                        $rangoInicio = $actual;
                    }
                    $rangoPrevio = $actual;
                }

                // Asegurarse de añadir el último rango
                if ($rangoInicio == $rangoPrevio) {
                    $rangos[] = (string)$rangoInicio;
                } else {
                    $rangos[] = $rangoInicio . ' - ' . $rangoPrevio;
                }
            }

            return response()->json(['rangos' => $rangos], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener seriales de actas: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudieron obtener los seriales.'], 500);
        }
    }

    /**
     * Obtiene los seriales de ACTAS asignados a un inspector y los agrupa en rangos.
     *
     * @param int $idInspector
     * @return JsonResponse
     */
    public function getSerialesAsignadosInspector($idInspector): JsonResponse
    {
        if (!$this->idStickerActa) {
            return response()->json(['error' => 'Sticker "ACTA" no configurado.'], 404);
        }

        try {
            // 1. Obtener todos los seriales asignados al inspector
            $seriales = TblStickerActaSerial::where('id_sticker_tipo', $this->idStickerActa)
                ->where('id_inspector', $idInspector)
                ->where('estado', 'asignado') // <-- Filtro clave
                ->orderBy('serial', 'asc')
                ->pluck('serial')
                ->toArray();

            // 2. Agrupar en rangos (misma lógica que la función de inventario)
            $rangos = [];
            if (count($seriales) > 0) {
                $rangoInicio = $seriales[0];
                $rangoPrevio = $seriales[0];

                for ($i = 1; $i < count($seriales); $i++) {
                    $actual = $seriales[$i];
                    if ($actual != $rangoPrevio + 1) {
                        if ($rangoInicio == $rangoPrevio) {
                            $rangos[] = (string)$rangoInicio;
                        } else {
                            $rangos[] = $rangoInicio . ' - ' . $rangoPrevio;
                        }
                        $rangoInicio = $actual;
                    }
                    $rangoPrevio = $actual;
                }

                // Añadir el último rango
                if ($rangoInicio == $rangoPrevio) {
                    $rangos[] = (string)$rangoInicio;
                } else {
                    $rangos[] = $rangoInicio . ' - ' . $rangoPrevio;
                }
            }

            return response()->json(['rangos' => $rangos], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener seriales asignados: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudieron obtener los seriales.'], 500);
        }
    }

}
