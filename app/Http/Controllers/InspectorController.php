<?php

namespace App\Http\Controllers;

use App\Models\tbl_insp_cali;
use App\Models\User;
use Illuminate\Http\Request;

class InspectorController extends Controller
{
    public function index()
    {
        $inspectores = tbl_insp_cali::where('state', 1)->get();
        $supervisores = User::role('Supervisor')->get();
        return view('inspectores.index', compact('inspectores', 'supervisores'));
    }

    public function create()
    {
        $supervisores = User::role('Supervisor')->get();
        return view('inspectores.index', compact('supervisores'));


    public function store(){
        $validatedData = request()->validate([
            'nombres' => 'required',
            'apellidos' => 'required',
            'type_id' => 'required',
            'cedula' => 'required|unique:tbl_insp_cali',
            'supervisor' => 'required',
        ],[
            'nombres.required' => 'El campo nombres es obligatorio',
            'apellidos.required' => 'El campo apellidos es obligatorio',
            'type_id.required' => 'El campo tipo de identificación es obligatorio',
            'cedula.required' => 'El campo cédula es obligatorio',
            'cedula.unique' => 'La cédula ingresada ya existe en la base de datos',
            'supervisor.required' => 'El campo supervisor es obligatorio',
        ]);
        $inspector = new tbl_insp_cali();
        $inspector->nombres = request()->nombres;
        $inspector->apellidos = request()->apellidos;
        $inspector->type_id = request()->type_id;
        $inspector->cedula = request()->cedula;
        $inspector->state = 1;
        $inspector->SUPERVISOR = request()->supervisor;
        $inspector->aprendizo = 1;
        $inspector->save();
        return redirect()->route('inspectores.index');
    }

    public function edit(tbl_insp_cali $inspector)
    {

        $nombres = $request->input('nombres');
        $apellidos = $request->input('apellidos');
        $type_id = $request->input('type_id');
        $cedula = $request->input('cedula');
        $supervisor = $request->input('supervisor');

        if ($nombres == "" || $apellidos == "" || $type_id == "" || $cedula == "" || $supervisor == "") {
            return response()->json([
                'status' => 'error',
                'message' => 'Todos los campos son obligatorios'
            ]);
        } else {
            $validarCedula = tbl_insp_cali::where("cedula", $cedula)->first();
            if ($validarCedula != null) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'La cédula ya existe'
                ]);
            } else {
                $inspector->nombres = $nombres;
                $inspector->apellidos = $apellidos;
                $inspector->type_id = $type_id;
                $inspector->cedula = $cedula;
                $inspector->SUPERVISOR = $supervisor;
                $inspector->state = 1;
                $inspector->save();

                $inspectorGuardar = tbl_insp_cali::with('supervisor')
                        ->where('state', 1)
                        ->orderBy('id', 'desc')
                        ->first();

                if ($inspector) {
                    return response()->json([
                        'status' => 'success',
                        'inspector' => $inspectorGuardar,
                        'message' => 'El inspector se registro con exito'
                    ]);
                } else {
                    return response()->json([
                        'status' => 'errorRegistro',
                        'message' => 'Error al registrar el inspector'
                    ]);
                }
            }
        }
    }

    public function update(Request $request)
    {

        $id = $request->input('id');
        $nombre = $request->input('nombres');
        $apellidos = $request->input('apellidos');
        $supervisor = $request->input('supervisor');

        if ($nombre == "" || $apellidos == "") {
            return response()->json([
                'status' => 'error',
                'message' => 'Los nombres y apellidos son obligatorios'
            ]);
        } else {
            $inspector = tbl_insp_cali::find($id);
            $inspector->nombres = $nombre;
            $inspector->apellidos = $apellidos;
            $inspector->SUPERVISOR = $supervisor;
            $inspector->save();

            if ($inspector) {
                return response()->json([
                    'status' => 'success',
                    'inspector' => $inspector,
                    'message' => 'Inspector actualizado con éxito.'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al actualizar el inspector'
                ], 500);
            }
        }

        $validatedData = $request->validate([
            'nombres' => 'required',
            'apellidos' => 'required',
            'supervisor' => 'required',

        ],[
            'nombres.required' => 'El campo nombres es obligatorio',
            'apellidos.required' => 'El campo apellidos es obligatorio',
            'supervisor.required' => 'El campo supervisor es obligatorio',
        ]);
        $inspector->nombres = $request->nombres;
        $inspector->apellidos = $request->apellidos;
        $inspector->SUPERVISOR = $request->supervisor;
        $inspector->aprendiz = $request->aprendiz;
        $inspector->save();
        return redirect()->route('inspectores.index');
    }

    public function change_state(Request $request, $id)
    {
        $inspector = tbl_insp_cali::with('supervisor')
                        ->find($id);

        if($inspector->state === 1){
            $inspector->state = 0;
            $inspector->save();
            if($inspector){
                return response()->json([
                    'status'=>'success',
                    'inspector'=>$inspector
                ]);
            }else{
                return response()->json([
                    'status'=>'error',
                ]);
            }
        }else{
            $inspector->state = 1;
            $inspector->save();
            if($inspector){
                return response()->json([
                    'status'=>'success',
                    'inspector'=>$inspector
                ]);
            }else{
                return response()->json([
                    'status'=>'error',
                ]);
            }
        }
    }

    public function show_disabled()
    {
        $inspectores = tbl_insp_cali::with("supervisor")
                            ->where('state', 0)->get();
        return response()->json([
            'inspector' => $inspectores
        ]);
    }

    public function getDataInspector(Request $request)
    {
        $id = intVal($request->input('id'));

        $inspectorData = tbl_insp_cali::where('id',  $id)->first();

        if ($inspectorData != null) {
            $arrayInspector = [
                'id' => $inspectorData->id,
                'nombres' => $inspectorData->nombres,
                'apellidos' => $inspectorData->apellidos,
                'type_id' => $inspectorData->type_id,
                'cedula' => $inspectorData->cedula,
                'supervisor' => $inspectorData->SUPERVISOR
            ];

            return response()->json([
                'inspector' => $arrayInspector
            ]);
        }
    }
}
