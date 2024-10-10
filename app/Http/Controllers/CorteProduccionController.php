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
        $corte->nombre = $request->datos['nombre'];
        $corte->fecha_inicio = $request->datos['fecha_inicio'];
        $corte->fecha_fin = $request->datos['fecha_fin'];
        $corte->meta = $request->datos['meta'];
        $corte->dobles = $request->datos['dobles'];
        
        // Validación de fechas
        $errores = [];
        
        // 1. Validar que fecha_inicio y fecha_fin no sean iguales
        if ($corte->fecha_inicio === $corte->fecha_fin) {
          $errores[] = "La fecha de inicio no puede ser igual a la fecha de fin.";
        }
        
        // 2. Validar que el rango de fechas no se solape con otro existente
        $solapamiento = tbl_produccion_corte::where(function ($query) use ($corte) {
          $query->whereBetween('fecha_inicio', [$corte->fecha_inicio, $corte->fecha_fin])
                ->orWhereBetween('fecha_fin', [$corte->fecha_inicio, $corte->fecha_fin])
                ->orWhere(function ($q) use ($corte) {
                  $q->where('fecha_inicio', '<', $corte->fecha_inicio)
                    ->where('fecha_fin', '>', $corte->fecha_fin);
                });
        })->where('id', '!=', $corte->id)->first(); // Excluir el registro actual si está editando
        
        if ($solapamiento) {
          $errores[] = "El rango de fechas se solapa con un corte existente. solapamiento: " . $solapamiento->nombre ." ". $solapamiento->fecha_inicio . " ". $solapamiento->fecha_fin;
        }
        
        // Si hay errores, mostrarlos y no guardar el registro
        if (!empty($errores)) {
          return response()->json(['errors' => $errores]); 
          // o cualquier otra forma de manejar los errores que uses
        }
        
        $corte->save();

        session()->flash('success', 'Corte creado correctamente');
        return response()->json(['success' => $corte]);
    }

    public function storeMunicipio(Request $request)
    {
        
        $municipio = new tbl_localidades_municipio();
        $municipio->nombre = $request->datos['nombre'];
        $municipio->id_sede = $request->datos['sede'];
        $municipio->id_zona = $request->datos['zona'];
        $municipio->save();

        session()->flash('success', 'Municipio creado correctamente');
        return response()->json(['success' => $municipio]);
    }

    public function storeSede(Request $request)
    {
        
        $sede = new tbl_localidades_sede();
        $sede->nombre = $request->datos['nombre'];
        $sede->save();

        session()->flash('success', 'Sede creada correctamente');
        return response()->json(['success' => $sede]);
    }

    public function storeCausal(Request $request)
    {
        
        $causal = new tbl_bitacoras_causal();
        $causal->nom_causal = $request->datos['nombre'];
        $causal->save();

        session()->flash('success', 'Causal creado correctamente');
        return response()->json(['success' => $causal]);
    }

    public function editCausal($id)
    {
        $causal = tbl_bitacoras_causal::find($id);
        return response()->json([$causal]);
    }

    public function updateCausal(Request $request, $id)
    {
        $causal = tbl_bitacoras_causal::find($id);
        $causal->nom_causal = $request->datos['nombre'];
        $causal->save();

        session()->flash('success', 'Causal actualizado correctamente');
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
        $corte->nombre = $request->datos['nombre'];
        $corte->fecha_inicio = $request->datos['fecha_inicio'];
        $corte->fecha_fin = $request->datos['fecha_fin'];
        $corte->meta = $request->datos['meta'];
        $corte->dobles = $request->datos['dobles'];
        
        // Validación de fechas
        $errores = [];
        
        // 1. Validar que fecha_inicio y fecha_fin no sean iguales
        if ($corte->fecha_inicio === $corte->fecha_fin) {
          $errores[] = "La fecha de inicio no puede ser igual a la fecha de fin.";
        }
        
        // 2. Validar que el rango de fechas no se solape con otro existente
        $solapamiento = tbl_produccion_corte::where(function ($query) use ($corte) {
          $query->whereBetween('fecha_inicio', [$corte->fecha_inicio, $corte->fecha_fin])
                ->orWhereBetween('fecha_fin', [$corte->fecha_inicio, $corte->fecha_fin])
                ->orWhere(function ($q) use ($corte) {
                  $q->where('fecha_inicio', '<', $corte->fecha_inicio)
                    ->where('fecha_fin', '>', $corte->fecha_fin);
                });
        })->where('id', '!=', $corte->id)->first(); // Excluir el registro actual si está editando
        
        if ($solapamiento) {
          $errores[] = "El rango de fechas se solapa con un corte existente. solapamiento: " . $solapamiento->nombre ." ". $solapamiento->fecha_inicio . " ". $solapamiento->fecha_fin;
        }
        
        // Si hay errores, mostrarlos y no guardar el registro
        if (!empty($errores)) {
          return response()->json(['errors' => $errores]); 
          // o cualquier otra forma de manejar los errores que uses
        }
        
        $corte->save();
        session()->flash('success', 'Corte actualizado correctamente');
        return response()->json(['success' => $corte]);
    }

    public function editMunicipio($id)
    {
        $municipio = tbl_localidades_municipio::find($id);
        return response()->json([$municipio]);
    }

    public function updateMunicipio(Request $request, $id)
    {
        $municipio = tbl_localidades_municipio::find($id);
        $municipio->nombre = $request->datos['nombre'];
        $municipio->id_sede = $request->datos['sede'];
        $municipio->id_zona = $request->datos['zona'];
        $municipio->save();

        session()->flash('success', 'Municipio actualizado correctamente');
        return response()->json(['success' => $municipio]);
    }
    public function editSede($id)
    {
        $sede = tbl_localidades_sede::find($id);
        return response()->json([$sede]);
    }


}
