<?php

namespace App\Http\Controllers;

use App\Models\TblInspCali;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InspectorController extends Controller
{
    public function index()
    {
        $inspectores = TblInspCali::where('state', 1)->get();
        $supervisores = User::role('Supervisor')->get();
        return view('inspectores.index', compact('inspectores', 'supervisores'));
    }

    public function create()
    {
        $supervisores = User::role('Supervisor')->get();
        return view('inspectores.index', compact('supervisores'));
    }


    public function store(Request $request, TblInspCali $inspector)
    {

        // Validar los datos enviados en la solicitud
        $validator = Validator::make(
            $request->all(),
            [
                'idCrear' => 'required|integer|unique:tbl_insp_cali,id',
                'nombres' => 'required|string|max:50',
                'apellidos' => 'required|string|max:50',
                'type_id' => 'required|string|max:10',
                'cedula' => 'required|string|max:20|unique:tbl_insp_cali,cedula',
                'supervisor' => 'required|integer'
            ],
            [
                // Mensajes de error personalizados
                'required' => 'Por favor complete todos los campos',
                'string' => 'Por favor solo ingresar texto',
                'nombres.max' => 'El campo nombres tiene un límite de 50 caracteres',
                'apellidos.max' => 'El campo apellidos tiene un límite de 50 caracteres',
                'cedula.unique' => 'Este número de identificación ya está registrado',
                'idCrear.unique' => 'El ID ingresado ya existe'
            ]
        );

        // Si la validación falla, se devuelve un error en formato JSON con código 422 (Unprocessable Entity)
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {

            // Iniciar una transacción en la base de datos
            DB::beginTransaction();

            // Crear una nueva instancia del modelo tbl_insp_cali
            $inspector->id = $request->input('idCrear');
            $inspector->nombres = $request->input('nombres');
            $inspector->apellidos = $request->input('apellidos');
            $inspector->type_id = $request->input('type_id');
            $inspector->cedula = $request->input('cedula');
            $inspector->SUPERVISOR = $request->input('supervisor');
            $inspector->state = 1;
            $inspector->save();

            // Obtener el último inspector creado con su relación de supervisor
            $inspectorGuardar = TblInspCali::with('supervisor')
                ->where('state', 1)
                ->orderBy('id', 'desc')
                ->first();

            // Confirmar la transacción si todo salió bien
            DB::commit();

            // Retornar una respuesta JSON con mensaje de éxito y los datos del inspector recién creado
            return response()->json([
                'success' => 'Inspector creado con exito',
                'inspector' => $inspector->load('supervisor') // Cargar la relación con el supervisor
            ], 200);
        } catch (\Exception $e) { // Capturar cualquier error que ocurra en el proceso
            // Si hay un error, revertir la transacción
            DB::rollBack();
            // Registrar el error en el log del sistema
            Log::error($e->getMessage());
            // Retornar un mensaje de error en formato JSON con código 500 (Internal Server Error)
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        // Validar los datos enviados en la solicitud
        $validator = Validator::make(
            $request->all(),
            [
                'nombres' => 'required|string|max:50',
                'apellidos' => 'required|string|max:50',
                'supervisor' => 'required|integer',
                'aprendiz' => 'nullable|integer'
            ],
            [
                // Mensajes de error personalizados
                'required' => 'Por favor complete todos los campos',
                'string' => 'Por favor solo ingresar texto',
                'nombres.max' => 'El campo nombres tiene un límite de 50 caracteres',
                'apellidos.max' => 'El campo apellidos tiene un límite de 50 caracteres',
            ]
        );

        // Si la validación falla, se devuelve un error en formato JSON con código 422
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            // Iniciar una transacción en la base de datos
            DB::beginTransaction();

            // Buscar el inspector por ID
            $inspector = TblInspCali::findOrFail($request->input('id'));

            // Actualizar los valores
            $inspector->nombres = $request->input('nombres');
            $inspector->apellidos = $request->input('apellidos');
            $inspector->SUPERVISOR = $request->input('supervisor');
            $inspector->aprendiz = $request->input('aprendiz'); // Si no se envía, se deja como null
            $inspector->save();

            // Confirmar la transacción si todo salió bien
            DB::commit();

            // Retornar una respuesta JSON con mensaje de éxito y los datos actualizados
            return response()->json([
                'success' => 'Inspector actualizado con éxito',
                'inspector' => $inspector
            ], 200);
        } catch (\Exception $e) { // Capturar cualquier error
            // Si hay un error, revertir la transacción
            DB::rollBack();
            // Registrar el error en el log del sistema
            Log::error($e->getMessage());
            // Retornar un mensaje de error en formato JSON con código 500
            return response()->json(['error' => 'Error al actualizar el inspector: ' . $e->getMessage()], 500);
        }
    }


    public function change_state($id)
    {
        try {
            // Busca el inspector por su ID junto con su supervisor
            $inspector = TblInspCali::with('supervisor')->find($id);

            // Si no se encuentra el inspector, devuelve un error
            if (!$inspector) {
                return response()->json(['error' => 'Inspector no encontrado'], 422);
            }

            // Cambia el estado del inspector (si está activo lo desactiva y viceversa)
            $inspector->state = !$inspector->state;
            $inspector->save();

            // Retorna una respuesta JSON con el mensaje de éxito y el inspector actualizado
            return response()->json([
                'success' => 'Estado cambiado exitosamente',
                'inspector' => $inspector
            ]);
        } catch (\Exception $e) {
            // Captura cualquier error y devuelve una respuesta JSON con un mensaje de error
            return response()->json(['error' => 'Error al cambiar el estado ' . $e->getMessage()], 500);
        }
    }


    public function show_disabled()
    {
        try {
            // Obtiene todos los inspectores desactivados (state = 0) incluyendo la relación con su supervisor
            $inspectores = TblInspCali::with("supervisor")->where('state', 0)->get();

            // Retorna la lista de inspectores desactivados en formato JSON
            return response()->json([
                'inspectores' => $inspectores
            ]);
        } catch (\Exception $e) {
            // Captura cualquier error y devuelve una respuesta JSON con un mensaje de error
            return response()->json(['error' => 'Error al obtener los inspectores desactivados ' . $e->getMessage()], 500);
        }
    }


    public function getDataInspector(Request $request)
    {
        try {
            // Obtiene el ID del inspector desde la solicitud y lo convierte a entero
            $id = intval($request->input('id'));
            // Busca el inspector por su ID junto con su supervisor
            $inspector = TblInspCali::with('supervisor')->find($id);

            // Si no se encuentra el inspector, devuelve un error
            if (!$inspector) {
                return response()->json(['error' => 'Inspector no encontrado'], 422);
            }

            // Retorna los datos del inspector en formato JSON
            return response()->json([
                'inspector' => [
                    'id' => $inspector->id,
                    'nombres' => $inspector->nombres,
                    'apellidos' => $inspector->apellidos,
                    'type_id' => $inspector->type_id,
                    'cedula' => $inspector->cedula,
                    'supervisor' => $inspector->SUPERVISOR,
                    'aprendiz' => $inspector->aprendiz
                ]
            ]);
        } catch (\Exception $e) {
            // Captura cualquier error y devuelve una respuesta JSON con un mensaje de error
            return response()->json(['error' => 'Error al obtener los datos del inspector ' . $e->getMessage()], 500);
        }
    }
}
