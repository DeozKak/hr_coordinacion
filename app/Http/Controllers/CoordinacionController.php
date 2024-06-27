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

    public function getdataCoordinacion(Request $request)
    {
        /*  $porPagina = 100; // Cantidad de registros por página
        $pagina = $request->input('pagina', 1); // Obtener el número de página de la solicitud

        $offset = ($pagina - 1) * $porPagina;

        $datos = asignadas::whereIn('tipo_trabajo', [10444, 12161]) // Seleccionar solo las columnas necesarias
            ->skip($offset)
            ->take($porPagina)
            ->get();

        return response()->json($datos);
 */
        $datos =  asignadas::whereIn('tipo_trabajo', [12162, 12163, 12164])->get();


        return response()->json($datos);
        // return asignadas::whereIn('tipo_trabajo', [10444, 12161])->get()->take(50)->toJson();

    }

    public function filterData(Request $request)
    {
        $filters = $request->input('filters');
        $columnMapping = [
            '0' => 'orden',
            '1' => 'contrato',
            '2' => 'producto',
            '3' => 'numero_solicitud',
            '4' => 'tipo_solicitud',
            '5' => 'NIT_CC',
            '6' => 'nombre_lugar',
            '7' => 'departamento',
            '8' => 'localidad',
            '9' => 'sector_operativo',
            '10' => 'direccion',
            '11' => 'consecutivo_ruta',
            '12' => 'telefono',
            '13' => 'medidor',
            '14' => 'categoria',
            '15' => 'unidad_operativa',
            '16' => 'tipo_trabajo',
            '17' => 'fecha_asignacion',
            '18' => 'observacion_solicitud',
            // Agrega más mapeos de índices a nombres de columnas según sea necesario
        ];
        
        $query = asignadas::whereIn('tipo_trabajo', [10444, 12161]);
  
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $index = $filter['column'];
                $column = $columnMapping[$index] ?? null;
                if ($column) {
                $operation = $filter['operation'];
                $conditions = $filter['conditions'];
                // Procesar los datos de los filtros y aplicar la lógica de filtrado en la consulta
              
                 
                    $values = $conditions[1]['args'][0]; // Obtener el valor del filtro
                
                    $query->whereIn($column, $values);
                }
            }    
            
        }

        $datosFiltrados = $query->get();

        return response()->json($datosFiltrados);
    }
}
