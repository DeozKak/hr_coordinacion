<?php

namespace App\Http\Controllers;
use App\Models\tbl_produccion_corte;
use App\Models\tbl_localidades_municipio;
use App\Models\tbl_localidades_sede;
use App\Models\tbl_produccion_zona;
use App\Models\tbl_bitacoras_causal;
use Illuminate\Http\Request;

class CorteProduccionController extends Controller
{

    public function index()
    {
        $cortes = tbl_produccion_corte::all();
        $municipios = tbl_localidades_municipio::all();
        $sedes = tbl_localidades_sede::all();
        $zonas = tbl_produccion_zona::all();
        $causales = tbl_bitacoras_causal::all();

        return view('corte.index', compact('cortes','municipios','sedes','zonas','causales'));
    }


    public function storeCorte(Request $request)
    {
        $corte = new tbl_produccion_corte();
        $corte->nombre = $request->nombre;
        $corte->fecha_inicio = $request->fecha_inicio;
        $corte->fecha_fin = $request->fecha_fin;
        $corte->meta = $request->meta;
        $corte->dobles = $request->dobles;

        // Validación de fechas
        // $errores = [];

        // 1. Validar que fecha_inicio y fecha_fin no sean iguales
        if ($corte->fecha_inicio === $corte->fecha_fin) {
            return response()->json([
                'status' => 'igual',
                'message' => 'La fecha de inicio no puede ser igual a la fecha de fin.',
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
        $solapamiento = tbl_produccion_corte::where(function ($query) use ($corte) {
          $query->whereBetween('fecha_inicio', [$corte->fecha_inicio, $corte->fecha_fin])
                ->orWhereBetween('fecha_fin', [$corte->fecha_inicio, $corte->fecha_fin])
                ->orWhere(function ($q) use ($corte) {
                  $q->where('fecha_inicio', '<', $corte->fecha_inicio)
                    ->where('fecha_fin', '>', $corte->fecha_fin);
                });
        })->where('id', '!=', $corte->id)->first(); // Excluir el registro actual si está editando

        if ($solapamiento) {
            return response()->json([
                'status' => 'solapamiento',
                'message' => 'El rango de fechas se solapa con un corte existente. solapamiento: ' . $solapamiento->nombre .' '. $solapamiento->fecha_inicio . ' '. $solapamiento->fecha_fin,
            ]);
        }

        // Si hay errores, mostrarlos y no guardar el registro
        if (!empty($errores)) {
          return response()->json(['errors' => $errores]);
          // o cualquier otra forma de manejar los errores que uses
        }

        $corte->save();

        return response()->json(['success' => $corte]);
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

    public function storeSede(Request $request)
    {
        // validar el nombre de la causal
        $sqlSede = tbl_localidades_sede::where('nombre', $request->nombre)->first();

        if($sqlSede != null){
            return response()->json([
                'status' => 'exist',
                'message' => 'La sede ya existe.',
            ]);
        }

        $sede = new tbl_localidades_sede();
        $sede->nombre = $request->nombre;
        $sede->save();

        return response()->json(['success' => $sede]);
    }

    public function storeZona(Request $request)
    {

        // validar el nombre de la causal
        $sqlZona = tbl_produccion_zona::where('nombre', $request->nombre)->first();

        if($sqlZona != null){
            return response()->json([
                'status' => 'exist',
                'message' => 'La zona ya existe.',
            ]);
        }

        $zona = new tbl_produccion_zona();
        $zona->nombre = $request->nombre;
        $zona->save();

        return response()->json(['success' => $zona]);
    }

    public function storeCausal(Request $request)
    {

        // validar el nombre de la causal
        $sqlCausal = tbl_bitacoras_causal::where('nom_causal', $request->nom_causal)->first();

        if($sqlCausal != null){
            return response()->json([
                'status' => 'exist',
                'message' => 'El causal ya existe.',
            ]);
        }

        $causal = new tbl_bitacoras_causal();
        $causal->nom_causal = $request->nom_causal;
        $causal->save();

        return response()->json(['success' => $causal]);
    }

    public function editCausal($id)
    {
        $causal = tbl_bitacoras_causal::find($id);
        return response()->json([$causal]);
    }

    public function updateCausal(Request $request, $id)
    {
        $sqlCausal = tbl_bitacoras_causal::where('nom_causal', $request->nombre)->first();

        if($sqlCausal != null && $sqlCausal['id'] != $id){
            return response()->json([
                'status' => 'exist',
                'message' => 'El causal ya existe.',
            ]);
        }
        $causal = tbl_bitacoras_causal::find($id);
        $causal->nom_causal = $request->nombre;
        $causal->save();

        return response()->json(['success' => $causal]);
    }

    public function editCorte($id)
    {
        $corte = tbl_produccion_corte::find($id);
        return response()->json([$corte]);
    }

    public function updateCorte(Request $request, $id)
    {
        $corte = tbl_produccion_corte::find($id);
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
        $solapamiento = tbl_produccion_corte::where(function ($query) use ($corte) {
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
                'message' => "El rango de fechas se solapa con un corte existente. solapamiento: " . $solapamiento->nombre ." ". $solapamiento->fecha_inicio . " ". $solapamiento->fecha_fin,
            ]);
        }

        $corte->save();
        return response()->json(['success' => $corte]);
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

        if($sqlMunicipio != null && $sqlMunicipio['id'] != $id){
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
    public function editSede($id)
    {
        $sede = tbl_localidades_sede::find($id);
        return response()->json([$sede]);
    }

    public function updateSede(Request $request, $id)
    {
        $sqlSede = tbl_localidades_sede::where('nombre', $request->nombre)->first();

        if($sqlSede != null && $sqlSede['id'] != $id){
            return response()->json([
                'status' => 'exist',
                'message' => 'La sede ya existe.',
            ]);
        }

        $sede = tbl_localidades_sede::find($id);
        $sede->nombre = $request->nombre;
        $sede->save();

        return response()->json(['success' => $sede]);
    }

    public function editZona($id)
    {
        $zona = tbl_produccion_zona::find($id);
        return response()->json([$zona]);
    }


    public function updateZona(Request $request, $id)
    {
        $sqlZona = tbl_produccion_zona::where('nombre', $request->nombre)->first();

        if($sqlZona != null && $sqlZona['id'] != $id){
            return response()->json([
                'status' => 'exist',
                'message' => 'La zona ya existe.',
            ]);
        }

        $zona = tbl_produccion_zona::find($id);
        $zona->nombre = $request->nombre;
        $zona->save();

        return response()->json(['success' => $zona]);
    }

    public function changeStatusSede(Request $request){
        $id = $request->input('id');
        $sede = tbl_localidades_sede::find($id);

        if($sede->status == 1){
            $sede->status = 0;
        }else{
            $sede->status = 1;
        }

        $sede->save();
        return response()->json(['success' => $sede]);
    }


    public function changeStatusZona(Request $request){
        $id = $request->input('id');
        $zona = tbl_produccion_zona::find($id);

        if($zona->status == 1){
            $zona->status = 0;
        }else{
            $zona->status = 1;
        }

        $zona->save();
        return response()->json(['success' => $zona]);
    }

    public function changeStatusCausal(Request $request){
        $id = $request->input('id');
        $causal = tbl_bitacoras_causal::find($id);

        if($causal->status == 1){
            $causal->status = 0;
        }else{
            $causal->status = 1;
        }

        $causal->save();
        return response()->json(['success' => $causal]);
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

