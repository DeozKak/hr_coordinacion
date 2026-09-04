<?php

namespace App\Http\Controllers\Produccion;

use App\Http\Controllers\Controller;
use App\Models\Bitacoras\TblBitacorasCausal;
use App\Models\Produccion\TblProduccionCorte;
use App\Models\Produccion\TblProduccionHistorico;
use App\Models\Produccion\TblProduccionZona;
use App\Models\Zonificacion\TblLocalidadesSede;
use App\Http\Requests\Produccion\ActualizarCorteRequest;
use App\Http\Requests\Produccion\CambiarEstadoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Rules\SolapamientoCorte;
use Illuminate\Support\Facades\Validator;

class CorteProduccionController extends Controller
{

    public function index()
    {
        $cortes = TblProduccionCorte::all();
        $sedes = TblLocalidadesSede::all();
        $zonas = TblProduccionZona::all();
        $causales = TblBitacorasCausal::all();

        return view('corte.index', compact('cortes', 'sedes', 'zonas', 'causales'));
    }


    // ------------------- CRUD TABLA tbl_produccion_corte ----------------------------------


    public function storeCorte(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'meta' => 'required|integer|max:250',
            'dobles' => 'required|integer|max:50',


        ], [
            'nombre.required' => 'Llene por favor el campo nombre',
            'nombre.string' => 'El nombre debe ser una cadena de texto válida',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres',

            'fecha_inicio.required' => 'Debe seleccionar una fecha de inicio',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida',

            'fecha_fin.required' => 'Debe seleccionar una fecha de finalización',
            'fecha_fin.date' => 'La fecha de finalización debe ser una fecha válida',
            'fecha_fin.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio',

            'meta.required' => 'Debe ingresar la meta',
            'meta.integer' => 'La meta debe ser un número entero',
            'meta.max' => 'La meta no puede ser mayor a 250',

            'dobles.required' => 'Debe ingresar la cantidad de dobles',
            'dobles.integer' => 'La cantidad de dobles debe ser un número entero',
            'dobles.max' => 'La cantidad de dobles no puede superar 50'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();
            $corte = new TblProduccionCorte();
            $corte->nombre = $request->nombre;
            $corte->fecha_inicio = $request->fecha_inicio;
            $corte->fecha_fin = $request->fecha_fin;
            $corte->meta = $request->meta;
            $corte->dobles = $request->dobles;

            //Validación de duplicados
            $duplicado = TblProduccionCorte::where(function ($query) use ($corte) {
                $query->whereBetween('fecha_inicio', [$corte->fecha_inicio, $corte->fecha_fin])
                    ->orWhereBetween('fecha_fin', [$corte->fecha_inicio, $corte->fecha_fin])
                    ->orWhere(function ($q) use ($corte) {
                        $q->where('fecha_inicio', '<', $corte->fecha_inicio)
                            ->where('fecha_fin', '>', $corte->fecha_fin);
                    });
            })->where('id', '!=', $corte->id)->first();

            if ($duplicado) {
                return response()->json(['error' => 'El rango de fechas se solapa con otro corte existente.'], 422);
            }

            // Validación de fechas
            // $errores = [];


            // Si hay errores, mostrarlos y no guardar el registro
            if (!empty($errores)) {
                return response()->json(['errors' => $errores]);
                // o cualquier otra forma de manejar los errores que uses
            }

            $corte->save();

            $historicos = new TblProduccionHistorico();
            $historicos->id_corte = $corte->id;
            $historicos->save();
            DB::commit();
            return response()->json(['success' => $corte]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function editCorte($id)
    {
        $corte = TblProduccionCorte::find($id);
        return response()->json([$corte]);
    }


    public function updateCorte(ActualizarCorteRequest $request, $id)
    {
        $corte = TblProduccionCorte::find($id);
        $corte->nombre = $request->nombre;
        $corte->fecha_inicio = $request->fecha_inicio;
        $corte->fecha_fin = $request->fecha_fin;
        $corte->meta = $request->meta;
        $corte->dobles = $request->dobles;

        // 1. Validar que fecha_inicio y fecha_fin no sean iguales
        if ($corte->fecha_inicio === $corte->fecha_fin) {
            return response()->json([
                'status' => 'fechas_iguales',
                'message' => "La fecha de inicio no puede ser igual a la fecha de fin."
            ]);
        }

        // 2. Validar que fecha_inicio nos sea mayor a fecha_fin
        if ($corte->fecha_inicio > $corte->fecha_fin) {
            return response()->json([
                'status' => 'fechaMayor',
                'message' => 'La fecha de inicio no puede ser mayor a la fecha de fin.',
            ]);
        }

        // 3. Validar que el rango de fechas no se solape con otro existente
        $solapamiento = TblProduccionCorte::where(function ($query) use ($corte) {
            $query->whereBetween('fecha_inicio', [$corte->fecha_inicio, $corte->fecha_fin])
                ->orWhereBetween('fecha_fin', [$corte->fecha_inicio, $corte->fecha_fin])
                ->orWhere(function ($q) use ($corte) {
                    $q->where('fecha_inicio', '<', $corte->fecha_inicio)
                        ->where('fecha_fin', '>', $corte->fecha_fin);
                });
        })->where('id', '!=', $corte->id)->first(); // Excluir el registro actual si está editando

        if ($solapamiento) {
            return response()->json([
                'status' => 'error',
                'message' => "El rango de fechas se solapa con un corte existente. solapamiento: " . $solapamiento->nombre . " " . $solapamiento->fecha_inicio . " " . $solapamiento->fecha_fin,
            ]);
        }

        $corte->save();
        return response()->json(['success' => $corte]);
    }


    // ------------------- CRUD TABLA tbl_localidades_sede ----------------------------------

    public function storeSede(Request $request, TblLocalidadesSede $sede)
    {
        // Validar los datos enviados en la solicitud
        $validator = Validator::make(
            $request->all(),
            [
                'nombre' => 'required|string|max:100|unique:tbl_localidades_sedes,nombre',
            ],
            [
                'required' => 'Por favor ingrese el nombre de la sede.',
                'string' => 'El nombre de la sede debe ser un texto.',
                'nombre.max' => 'El nombre de la sede no puede superar los 100 caracteres.',
                'nombre.unique' => 'La sede ya existe.',
            ]
        );

        // Si la validación falla, devolver el primer error en formato JSON con código 422
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            // Iniciar una transacción en la base de datos
            DB::beginTransaction();

            // Crear una nueva instancia de la sede y asignar valores
            $sede->nombre = $request->input('nombre');
            $sede->save();

            // Confirmar la transacción si todo salió bien
            DB::commit();

            // Retornar una respuesta JSON con mensaje de éxito y los datos de la sede recién creada
            return response()->json([
                'success' => 'Sede creada con éxito',
                'sede' => $sede
            ], 200);
        } catch (\Exception $e) { // Capturar cualquier error que ocurra en el proceso
            // Si hay un error, revertir la transacción
            DB::rollBack();
            // Registrar el error en el log del sistema
            Log::error($e->getMessage());
            // Retornar un mensaje de error en formato JSON con código 500 (Internal Server Error)
            return response()->json(['error' => 'Error al crear la sede: ' . $e->getMessage()], 500);
        }
    }


    public function editSede($id)
    {
        try {
            $sede = TblLocalidadesSede::find($id);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json([$sede]);
    }


    public function updateSede(Request $request, $id)
    {
        // Validar los datos enviados en la solicitud
        $validator = Validator::make(
            $request->all(),
            [
                'nombre' => 'required|string|max:100|unique:tbl_localidades_sedes,nombre,' . $id
            ],
            [
                'required' => 'El nombre de la sede es obligatorio.',
                'string' => 'El nombre de la sede debe ser un texto.',
                'nombre.max' => 'El nombre de la sede no puede superar los 100 caracteres.',
                'nombre.unique' => 'El nombre de la sede ya existe.'
            ]
        );

        // Si la validación falla, retornar el primer error
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            // Iniciar una transacción en la base de datos
            DB::beginTransaction();

            // Buscar la sede por ID
            $sede = TblLocalidadesSede::find($id);

            // Verificar si la sede existe
            if (!$sede) {
                return response()->json(['error' => 'La sede no existe.'], 404);
            }

            // Actualizar el nombre de la sede
            $sede->nombre = $request->nombre;
            $sede->save();

            // Confirmar la transacción
            DB::commit();

            // Retornar respuesta con éxito
            return response()->json([
                'success' => 'Sede actualizada correctamente.',
                'sede' => $sede
            ], 200);
        } catch (\Exception $e) { // Capturar cualquier error
            // Si hay un error, revertir la transacción
            DB::rollBack();
            // Registrar el error en el log
            Log::error($e->getMessage());

            // Retornar mensaje de error
            return response()->json(['error' => 'Error al actualizar la sede: ' . $e->getMessage()], 500);
        }
    }


    // ------------------- CRUD TABLA tbl_produccion_zona ----------------------------------

    public function storeZona(Request $request, TblProduccionZona $zona)
    {
        // Validar los datos enviados en la solicitud
        $validator = Validator::make(
            $request->all(),
            [
                'nombre' => 'required|string|max:255|unique:tbl_produccion_zonas,nombre',
            ],
            [
                'required' => 'Por favor ingrese el nombre de la zona.',
                'string' => 'El nombre de la zona debe ser un texto.',
                'nombre.max' => 'El nombre de la zona no puede superar los 255 caracteres.',
                'nombre.unique' => 'La zona ya existe.',
            ]
        );

        // Si la validación falla, devolver el primer error en formato JSON con código 422
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            // Iniciar una transacción en la base de datos
            DB::beginTransaction();

            // Crear una nueva instancia de la zona y asignar valores
            $zona->nombre = $request->input('nombre');
            $zona->save();

            // Confirmar la transacción si todo salió bien
            DB::commit();

            // Retornar una respuesta JSON con mensaje de éxito y los datos de la zona recién creada
            return response()->json([
                'success' => 'Zona creada con éxito',
                'zona' => $zona
            ], 200);
        } catch (\Exception $e) { // Capturar cualquier error que ocurra en el proceso
            // Si hay un error, revertir la transacción
            DB::rollBack();
            // Registrar el error en el log del sistema
            Log::error($e->getMessage());
            // Retornar un mensaje de error en formato JSON con código 500 (Internal Server Error)
            return response()->json(['error' => 'Error al crear la zona: ' . $e->getMessage()], 500);
        }
    }


    public function editZona($id)
    {
        try {
            $zona = TblProduccionZona::find($id);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json([$zona]);
    }

    public function updateZona(Request $request, $id)
    {
        // Validar los datos enviados en la solicitud
        $validator = Validator::make(
            $request->all(),
            [
                'nombre' => 'required|string|max:255|unique:tbl_produccion_zonas,nombre,' . $id
            ],
            [
                'required' => 'El nombre de la zona es obligatorio.',
                'string' => 'El nombre de la zona debe ser un texto.',
                'nombre.max' => 'El nombre de la zona no puede superar los 255 caracteres.',
                'nombre.unique' => 'El nombre de la zona ya existe.'
            ]
        );

        // Si la validación falla, retornar el primer error
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            // Iniciar una transacción en la base de datos
            DB::beginTransaction();

            // Buscar la zona por ID
            $zona = TblProduccionZona::find($id);

            // Verificar si la zona existe
            if (!$zona) {
                return response()->json(['error' => 'La zona no existe.'], 404);
            }

            // Actualizar el nombre de la zona
            $zona->nombre = $request->nombre;
            $zona->save();

            // Confirmar la transacción
            DB::commit();

            // Retornar respuesta con éxito
            return response()->json([
                'success' => 'Zona actualizada correctamente.',
                'zona' => $zona
            ], 200);
        } catch (\Exception $e) { // Capturar cualquier error
            // Si hay un error, revertir la transacción
            DB::rollBack();
            // Registrar el error en el log
            Log::error($e->getMessage());

            // Retornar mensaje de error
            return response()->json(['error' => 'Error al actualizar la zona: ' . $e->getMessage()], 500);
        }
    }


    // ------------------- CRUD TABLA tbl_bitacoras_causal ----------------------------------

    public function storeCausal(Request $request, TblBitacorasCausal $causal)
    {
        // Validar los datos enviados en la solicitud
        $validator = Validator::make(
            $request->all(),
            [
                'nom_causal' => 'required|string|max:255|unique:tbl_bitacoras_causales,nom_causal',
            ],
            [
                'required' => 'Por favor ingrese el nombre del causal.',
                'string' => 'El nombre del causal debe ser un texto.',
                'nom_causal.max' => 'El nombre del causal no puede superar los 255 caracteres.',
                'nom_causal.unique' => 'El nombre del causal ya existe.',
            ]
        );

        // Si la validación falla, devolver el primer error en formato JSON con código 422
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            // Iniciar una transacción en la base de datos
            DB::beginTransaction();

            // Crear una nueva instancia de causal y asignar valores
            $causal->nom_causal = $request->input('nom_causal');
            $causal->save();

            // Confirmar la transacción si todo salió bien
            DB::commit();

            // Retornar una respuesta JSON con mensaje de éxito y los datos del causal recién creado
            return response()->json([
                'success' => 'Causal creado con éxito',
                'causal' => $causal
            ], 200);
        } catch (\Exception $e) { // Capturar cualquier error que ocurra en el proceso
            // Si hay un error, revertir la transacción
            DB::rollBack();
            // Registrar el error en el log del sistema
            Log::error($e->getMessage());
            // Retornar un mensaje de error en formato JSON con código 500 (Internal Server Error)
            return response()->json(['error' => 'Error al crear el causal: ' . $e->getMessage()], 500);
        }
    }


    public function editCausal($id)
    {
        try {
            $causal = TblBitacorasCausal::find($id);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json([$causal]);
    }


    public function updateCausal(Request $request, $id)
    {
        // Validar los datos enviados en la solicitud
        $validator = Validator::make(
            $request->all(),
            [
                'nombre' => 'required|string|max:255|unique:tbl_bitacoras_causales,nom_causal,' . $id
            ],
            [
                'required' => 'El nombre del causal es obligatorio.',
                'string' => 'El nombre debe ser un texto.',
                'nombre.max' => 'El nombre del causal no puede superar los 255 caracteres.',
                'nombre.unique' => 'El nombre del causal ya existe.'
            ]
        );

        // Si la validación falla, retornar el primer error
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            // Iniciar una transacción en la base de datos
            DB::beginTransaction();

            // Buscar el causal por ID
            $causal = TblBitacorasCausal::find($id);

            // Verificar si el causal existe
            if (!$causal) {
                return response()->json(['error' => 'El causal no existe.'], 404);
            }

            // Actualizar el nombre del causal
            $causal->nom_causal = $request->nombre;
            $causal->save();

            // Confirmar la transacción
            DB::commit();

            // Retornar respuesta con éxito
            return response()->json([
                'success' => 'Causal actualizado con éxito.',
                'causal' => $causal
            ], 200);
        } catch (\Exception $e) { // Capturar cualquier error
            // Si hay un error, revertir la transacción
            DB::rollBack();
            // Registrar el error en el log
            Log::error($e->getMessage());

            // Retornar mensaje de error
            return response()->json(['error' => 'Error al actualizar el causal: ' . $e->getMessage()], 500);
        }
    }


    // ------------------- FUNCIONES CAMBIAR ESTADO ----------------------------------

    public function changeStatusSede(CambiarEstadoRequest $request)
    {
        try {
            $id = intval($request->input('id'));
            $sede = TblLocalidadesSede::find($id);

            if (!$sede) {
                return response()->json(['error' => 'Sede no encontrada'], 404);
            }

            $sede->status = !$sede->status;
            $sede->save();

            return response()->json([
                'success' => 'Estado de la sede actualizado exitosamente',
                'sede' => $sede
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cambiar el estado de la sede'], 500);
        }
    }


    public function changeStatusZona(CambiarEstadoRequest $request)
    {
        try {
            $id = intval($request->input('id'));
            $zona = TblProduccionZona::find($id);

            if (!$zona) {
                return response()->json(['error' => 'Zona no encontrada'], 404);
            }

            $zona->status = !$zona->status;
            $zona->save();

            return response()->json([
                'success' => 'Estado de la zona actualizado exitosamente',
                'zona' => $zona
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cambiar el estado de la zona'], 500);
        }
    }


    public function changeStatusCausal(CambiarEstadoRequest $request)
    {
        try {
            $id = intval($request->input('id'));
            $causal = TblBitacorasCausal::find($id);

            if (!$causal) {
                return response()->json(['error' => 'Causal no encontrada'], 404);
            }

            $causal->status = !$causal->status;
            $causal->save();

            return response()->json([
                'success' => 'Estado del causal actualizado exitosamente',
                'causal' => $causal
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cambiar el estado del causal'], 500);
        }
    }

}

