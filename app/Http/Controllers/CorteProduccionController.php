<?php

namespace App\Http\Controllers;
use App\Models\tbl_produccion_corte;
use App\Models\tbl_localidades_municipio;
use App\Models\tbl_localidades_sede;
use Illuminate\Http\Request;

class CorteProduccionController extends Controller
{
    
    public function index()
    {
        $cortes = tbl_produccion_corte::all();
        $municipios = tbl_localidades_municipio::all();
        $sedes = tbl_localidades_sede::all();
        
        return view('corte.index', compact('cortes','municipios','sedes'));
    }


}
