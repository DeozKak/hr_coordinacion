<?php

namespace App\Http\Controllers;

use App\Models\tbl_bitacora_archivo;
use App\Models\tbl_insp_cali;
use App\Models\tbl_bitacora_contrato;
use App\Models\tbl_produccion_corte;
use Illuminate\Http\Request;

class ProduccionController extends Controller
{
    public function index()
    {

        $fechaActual = date('Y-m-d');
        $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fechaActual)
            ->where('fecha_fin', '>=', $fechaActual)
            ->first();

        if (!$corte) {
           return view('produccion.index')->with('warning', 'No hay corte activo');
        }
        $contratosCorte = tbl_bitacora_contrato::where('FECHA', '>=', $corte->fecha_inicio)
        ->where('FECHA', '<=', $corte->fecha_fin)
        ->get();

        if (!$contratosCorte) {
            return view('produccion.index')->with('warning', 'No hay contratos en el corte activo');
        }

        $inpectores = tbl_insp_cali::all();

        if(!$inpectores){
            return view('produccion.index')->with('warning', 'No hay inspectores activos');
        }
        $produccionInspector = array();
        foreach($inpectores as $inspector){

            $numerosContratos = tbl_bitacora_contrato::where('CC_OPERARIO','=',$inspector->cedula)->where('FECHA', '>=', $corte->fecha_inicio)
            ->where('FECHA', '<=', $corte->fecha_fin)
            ->count();
            if($numerosContratos === 0){
                continue;
            }
            $produccionInspector[] =
            [
                'nombres' => $inspector->apellidos,
                'contratos' => $numerosContratos
            ];
           
        }

       
        return view('produccion.index', compact('produccionInspector'));
    }
}
