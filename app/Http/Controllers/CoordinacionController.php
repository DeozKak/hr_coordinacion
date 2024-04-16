<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\asignadas;

class CoordinacionController extends Controller
{
    public function coordinacion()
    {   
        return view('gestion.coordinacion');
    }

    public function getdataCoordinacion()
    {
        $asignadas = Asignadas::whereIn('tipo_trabajo', [10444,12161])->paginate(30000);
     
        return response()->json($asignadas);
    }
}
