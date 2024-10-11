<?php

namespace App\Http\Controllers;
use App\Models\tbl_insp_cali;
use App\Models\User;
use Illuminate\Http\Request;

class InspectorController extends Controller
{
    public function index()
    {
        $inspectores = tbl_insp_cali::where('state',1)->get();
    
        return view('inspectores.index', compact('inspectores'));
    }

    public function create()
    {
        $supervisores = User::role('Supervisor')->get();
        return view('inspectores.create', compact('supervisores'));
    }

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
        $supervisores = User::role('Supervisor')->get();
        return view('inspectores.edit', compact('inspector','supervisores'));
    }

    public function update(Request $request, tbl_insp_cali $inspector)
    {
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

    public function change_state(tbl_insp_cali $inspector)
    {
        if ($inspector->state == 0){
            $inspector->state = 1;
            $inspector->save();
        }else{
            $inspector->state = 0;
            $inspector->save();}

        return redirect()->back();
    }

    public function show_disabled()
    {
        $inspectores = tbl_insp_cali::where('state',0)->get();
        return view('inspectores.show_disabled', compact('inspectores'));
    }
    
    
}
