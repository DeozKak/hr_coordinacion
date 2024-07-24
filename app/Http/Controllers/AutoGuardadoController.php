<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\tbl_bitacora_contrato;
use App\Models\tbl_bitacora_archivo;
class AutoGuardadoController extends Controller
{
    public function buscar($nombre){

        $archivo = tbl_bitacora_archivo::where('NOMBRE_ARCHIVO', $nombre)->exists();

        return $archivo;
    }

    public function guardar(){

        $data = request()->all();
 
        return null;
    }
}
