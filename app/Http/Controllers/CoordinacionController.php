<?php

namespace App\Http\Controllers;

use App\DataTables\DataTable;
use Illuminate\Http\Request;
use App\Models\asignadas;

use Yajra\DataTables\DataTables;

class CoordinacionController extends Controller
{
    public function coordinacion()
    {
        return view('gestion.coordinacion');
    }

    public function getdataCoordinacionRP(Request $request)
    {
        $porPagina = 100; // Cantidad de registros por página
        $pagina = $request->input('pagina', 1); // Obtener el número de página de la solicitud

        $offset = ($pagina - 1) * $porPagina;

        $datos = asignadas::whereIn('tipo_trabajo', [10444, 12161]) // Seleccionar solo las columnas necesarias
            ->skip($offset)
            ->take($porPagina)
            ->get();

        return response()->json($datos);

        // return asignadas::whereIn('tipo_trabajo', [10444, 12161])->get()->take(50)->toJson();

    }
}
