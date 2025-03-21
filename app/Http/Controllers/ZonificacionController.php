<?php

namespace App\Http\Controllers;

use App\Models\Produccion\tbl_produccion_zona;
use App\Models\Zonificacion\tbl_localidades_sede;
use Illuminate\Http\Request;
use App\Models\Zonificacion\tbl_localidades_municipio;
use App\Models\Zonificacion\TblBarrios;

class ZonificacionController extends Controller
{

    public function index()
    {

        $municipios = tbl_localidades_municipio::all();
        $barrios = TblBarrios::all();
        $sedes = tbl_localidades_sede::all();
        $zonas = tbl_produccion_zona::all();


        return view('zonas.index', compact('municipios', 'sedes', 'zonas','barrios'));
    }

    public function storeMunicipio(Request $request)
    {
        // validar el nombre de la causal
        $sqlMunicipio = tbl_localidades_municipio::where('nombre', '=', $request->nombre)
            ->where('id_sede', '=', $request->sede)
            ->where('id_zona', '=', $request->zona)
            ->first();

        if ($sqlMunicipio) {
            return response()->json([
                'status' => 'exist',
                'message' => 'El municipio ya existe con la misma sede y zona.',
            ]);
        }

        $municipio = new tbl_localidades_municipio();
        $municipio->nombre = $request->nombre;
        $municipio->id_sede = $request->sede;
        $municipio->id_zona = $request->zona;
        $municipio->save();

        $sqlMun = tbl_localidades_municipio::with(['sede', 'zona'])
            ->where('id', $municipio->id)->first();

        return response()->json(['success' => $sqlMun]);
    }

    public function editMunicipio($id)
    {
        $municipio = tbl_localidades_municipio::find($id);
        return response()->json([$municipio]);
    }

    public function updateMunicipio(Request $request, $id)
    {
        $sqlMunicipio = tbl_localidades_municipio::where('nombre', '=', $request->nombre)
            ->where('id_sede', '=', $request->sede)
            ->where('id_zona', '=', $request->zona)
            ->first();

        if ($sqlMunicipio) {
            return response()->json([
                'status' => 'exist',
                'message' => 'El municipio ya existe con la misma sede y zona.',
            ]);
        }

        if ($sqlMunicipio != null && $sqlMunicipio['id'] != $id) {
            return response()->json([
                'status' => 'exist',
                'message' => 'El municipio ya existe.',
            ]);
        }

        $municipio = tbl_localidades_municipio::find($id);
        $municipio->nombre = $request->nombre;
        $municipio->id_sede = $request->sede;
        $municipio->id_zona = $request->zona;
        $municipio->save();

        $sqlMun = tbl_localidades_municipio::with(['sede', 'zona'])
            ->where('id', $municipio->id)->first();

        return response()->json(['success' => $sqlMun]);
    }

    public function changeStatusMunicipio(Request $request){
        $id = $request->input('id');
        $municipio = tbl_localidades_municipio::find($id);

        if($municipio->status == 1){
            $municipio->status = 0;
        }else{
            $municipio->status = 1;
        }

        $municipio->save();
        return response()->json(['success' => $municipio]);
    }
}
