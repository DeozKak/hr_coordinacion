<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\asignadas;

use Yajra\DataTables\DataTables;

class CoordinacionController extends Controller
{
    public function coordinacion()
    {   
        return view('gestion.coordinacion');
    }

    public function getdataCoordinacion(Request $request)
    {
        if ($request->ajax()) {
            $data = Asignadas::whereIn('tipo_trabajo', [10444,12161])->get();
            return DataTables::of($data)->make(true);
        }
       
    }
}
