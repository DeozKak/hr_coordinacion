<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\asignadas;
use App\Models\tbl_insp_cali;
use Illuminate\Support\Facades\DB;

class CoordinacionController extends Controller
{
    public function coordinacion()
    {
        return view('gestion.coordinacion');
    }

    public function getdataCoordinacion(Request $request)
    {

        $porPagina = 100; // Cantidad de registros por página


        // CONSULTAMOS LOS INPSECTORES
        $inspectores = tbl_insp_cali::select('id', 'nombres', 'apellidos')
            ->where('state', 1)
            ->get();


        // Crear un array para almacenar los datos con índice
        $datosConIndice = $datos->map(function ($item, $index) use ($offset) {


            $jornada = explode(" ", $item->HORA_INICIO);
            if (isset($jornada[1])) {
                $jornada = $jornada[1];
            } else {
                $jornada = "";
            }

            return [
                'indice' => $index + 1 + $offset,
                'orden' => $item->orden,
                'contrato' => $item->contrato,
                'producto' => $item->producto,
                'numero_solicitud' => $item->numero_solicitud,
                'tipo_solicitud' => $item->tipo_solicitud,
                'NIT_CC' => $item->NIT_CC,
                'nombre_lugar' => $item->nombre_lugar,
                'departamento' => $item->departamento,
                'localidad' => $item->localidad,
                'sector_operativo' => $item->sector_operativo,
                'direccion' => $item->direccion,
                'consecutivo_ruta' => $item->consecutivo_ruta,
                'telefono' => $item->telefono,
                'medidor' => $item->medidor,
                'categoria' => $item->categoria,
                'unidad_operativa' => $item->unidad_operativa,
                'tipo_trabajo' => $item->tipo_trabajo,
                'fecha_asignacion' => $item->fecha_asignacion,
                'observacion_solicitud' => $item->observacion_solicitud,
                'orden_solicitud_externa' => $item->orden_solicitud_externa,
                'tipo_solicitud_externa' => $item->tipo_solicitud_externa,
                'fecha_solicitud_externa' => $item->fecha_solicitud_externa,
                'observacion_externa' => $item->observacion_externa,
                'fecha_reasignacion_externa' => $item->fecha_reasignacion_externa,
                'FECHA_AGENDAMIENTO' => $item->FECHA_AGENDAMIENTO,
                'jornada' => $jornada,
                'CELULAR' => $item->CELULAR,
                'OBSERVACIONES' => $item->OBSERVACIONES,
                'estado_programacion' => $item->estado_programacion,
                'codigo_tecnico' => $item->codigo_tecnico,
                'fecha_asignacion_inspector' => $item->fecha_asignacion_inspector
            ];
        });

        // retornamos los tecnicos y el datosConIndice 
        return response()->json(
            [
                'estadoProgramacion' => $arrayEstPro,
                'inspectores' => $inspectores,
                'data' => $datosConIndice
            ]
        );
    }

    public function filterData(Request $request)
    {

        $valor = $request->input('valor');
        $tipo = $request->input('tipo');

        $porPagina = 100;
        $pagina = $request->input('pagina', 1);
        $offset = ($pagina - 1) * $porPagina;

        if ($valor != null) {
            $columnaBuscar = '';
            switch ($tipo) {
                case "0":
                    $columnaBuscar = "orden";
                    break;
                case "1":
                    $columnaBuscar = "contrato";
                    break;
                case "2":
                    $columnaBuscar = "producto";
                    break;
                case "3":
                    $columnaBuscar = "numero_solicitud";
                    break;
                case "4":
                    $columnaBuscar = "tipo_solicitud";
                    break;
                case "5":
                    $columnaBuscar = "NIT_CC";
                    break;
                case "6":
                    $columnaBuscar = "nombre_lugar";
                    break;
                case "7":
                    $columnaBuscar = "departamento";
                    break;
                case "8":
                    $columnaBuscar = "localidad";
                    break;
                case "9":
                    $columnaBuscar = "sector_operativo";
                    break;
                case "10":
                    $columnaBuscar = "direccion";
                    break;
                case "11":
                    $columnaBuscar = "consecutivo_ruta";
                    break;
                case "12":
                    $columnaBuscar = "telefono";
                    break;
                case "13":
                    $columnaBuscar = "medidor";
                    break;
                case "14":
                    $columnaBuscar = "categoria";
                    break;
                case "15":
                    $columnaBuscar = "unidad_operativa";
                    break;
                case "16":
                    $columnaBuscar = "tipo_trabajo";
                    break;
                case "17":
                    $columnaBuscar = "fecha_asignacion";
                    break;
                case "18":
                    $columnaBuscar = "observacion_solicitud";
                    break;
                default:
            }
            $datos = Asignadas::select('*')
                ->leftJoin('tbl_programacion_contratos', 'tbl_programacion_contratos.ORDEN_TRABAJO', '=', 'asignadas.orden')
                ->where($columnaBuscar, 'like', "%{$valor}%")
                ->whereIn('asignadas.tipo_trabajo', [10444, 12161])
                ->skip($offset)
                ->take($porPagina)
                ->get();

            $dataFinal = $datos->map(function ($item, $index) use ($offset) {

                $jornada = explode(" ", $item->HORA_INICIO);
                if (isset($jornada[1])) {
                    $jornada = $jornada[1];
                } else {
                    $jornada = "";
                }

                return [
                    'indice' => $index + 1 + $offset,
                    'orden' => $item->orden,
                    'contrato' => $item->contrato,
                    'producto' => $item->producto,
                    'numero_solicitud' => $item->numero_solicitud,
                    'tipo_solicitud' => $item->tipo_solicitud,
                    'NIT_CC' => $item->NIT_CC,
                    'nombre_lugar' => $item->nombre_lugar,
                    'departamento' => $item->departamento,
                    'localidad' => $item->localidad,
                    'sector_operativo' => $item->sector_operativo,
                    'direccion' => $item->direccion,
                    'consecutivo_ruta' => $item->consecutivo_ruta,
                    'telefono' => $item->telefono,
                    'medidor' => $item->medidor,
                    'categoria' => $item->categoria,
                    'unidad_operativa' => $item->unidad_operativa,
                    'tipo_trabajo' => $item->tipo_trabajo,
                    'fecha_asignacion' => $item->fecha_asignacion,
                    'observacion_solicitud' => $item->observacion_solicitud,
                    'orden_solicitud_externa' => $item->orden_solicitud_externa,
                    'tipo_solicitud_externa' => $item->tipo_solicitud_externa,
                    'fecha_solicitud_externa' => $item->fecha_solicitud_externa,
                    'observacion_externa' => $item->observacion_externa,
                    'fecha_reasignacion_externa' => $item->fecha_reasignacion_externa,
                    'FECHA_AGENDAMIENTO' => $item->FECHA_AGENDAMIENTO,
                    'jornada' => $jornada,
                    'CELULAR' => $item->CELULAR,
                    'OBSERVACIONES' => $item->OBSERVACIONES,
                    'estado_programacion' => $item->estado_programacion,
                    'codigo_tecnico' => $item->codigo_tecnico,
                    'fecha_asignacion_inspector' => $item->fecha_asignacion_inspector
                ];
            });

            return response()->json($dataFinal);
        } else {
            return $this->getdataCoordinacion($request);
        }
    }

    public function guardarProgramacionTecnico(Request $request)
    {
        $codigoTecnico = intval($request->input('codigoTecnico'));
        $orden = intval($request->input('ordenEnviar'));
        $estadoProgramacion = $request->input('estado');

        $campoActualizar = "";
        $valorActualizar = "";
        $fechaActual = "";
        $campoFecha = "";
        $parametros = [];

        if ($codigoTecnico !== NULL) {

            $fechaActual = date('Y-m-d');
            $campoFecha = ", fecha_asignacion_inspector = ?";
            $campoActualizar = "codigo_tecnico";
            $valorActualizar = $codigoTecnico;

            $tecnico = DB::table('tbl_insp_cali')->where('id', $codigoTecnico)->first();

            if ($tecnico == null) {
                echo 3;
                exit;
            }

            $parametros = [$valorActualizar, $fechaActual, $orden];
        } else if ($estadoProgramacion != null) {
            $campoActualizar = "estado_programacion";
            $valorActualizar = $estadoProgramacion;
            $parametros = [$valorActualizar, $orden];

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

        $asignadas = DB::update(
            "UPDATE asignadas 
                        SET {$campoActualizar} = ? 
                        {$campoFecha}
                        WHERE orden = ?",
            $parametros
        );

        if ($asignadas) {
            echo 1;
        } else {
            echo 2;
        }
    }
}
