<?php

namespace App\Http\Controllers\Coordinacion;


use App\Http\Controllers\Controller;
use App\Models\Coordinacion\asignadas;
use App\Models\Coordinacion\TblEstadosVne;
use App\Models\Coordinacion\TblRecepcion;
use App\Models\Coordinacion\TblRecepcionVneDetalle;
use App\Models\tbl_insp_cali;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AsignadasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $asignadas = Asignadas::all();

        return view('cargues.index', compact('asignadas'));
    }

    public function store(Request $request)
    {

        set_time_limit(400);
        ini_set('memory_limit', '1024M');

        $response = AsignadasController::uploadFile($request);

        if (is_object($response)) {
            return redirect()->route('cargues.load')->with('error', $response->errors()->first());
        }

        $spreadsheet = IOFactory::load($response);

        $array = AsignadasController::readExcel($spreadsheet);

        $skipFirstRow = false;
        $chunkSize = 1000; // Tamaño del lote

        $chunks = array_chunk($array, $chunkSize);

        foreach ($chunks as $chunk) {
            $asignadas = [];
            foreach ($chunk as $item) {
                if ($skipFirstRow === false) {
                    $skipFirstRow = true;
                    continue;
                }

                if ($item[1] != null) {
                    $asignadasExternas = DB::select(
                        "SELECT id, orden, tipo_trabajo FROM asignadas
                        WHERE contrato = ?",
                        [$item[1]]
                    );
                } else {
                    $asignadasExternas = [];
                }

                if (!empty($asignadasExternas)) {
                    // Actualizar solo si ambas condiciones no coinciden
                    if ($asignadasExternas[0]->orden != $item[0] && $asignadasExternas[0]->tipo_trabajo != $item[16]) {

                        $fechaNumero = intval($item[17]);
                        $fechaSolExt = Date::excelToDateTimeObject($fechaNumero);
                        $fechaSolExt = $fechaSolExt->format('Y-m-d');

                        DB::table('asignadas')
                            ->where('id', $asignadasExternas[0]->id)
                            ->where('status', 1)
                            ->update([
                                'orden_solicitud_externa' => $item[0],
                                'tipo_solicitud_externa' => $item[16],
                                'fecha_solicitud_externa' => $fechaSolExt,
                                'observacion_externa' => $item[18],
                                'fecha_reasignacion_externa' => date('Y-m-d'),
                            ]);
                    } else {
                        DB::table('asignadas')
                            ->where('orden', $item[0])
                            ->where('status', 1)
                            ->update([
                                'estado_producto' => $item[19],
                                'ult_comentario' => $item[21],
                                'updated_at' => now(),
                            ]);
                    }
                } else {
                    // Si no se encuentra en la base de datos, insertar
                    $num_entero = intval($item[23]);
                    $fecha_vence = Date::excelToDateTimeObject($num_entero);
                    $vence = $fecha_vence->format('Y-m-d');

                    $num_entero_ultima_cert = intval($item[22]);
                    $fecha_ult_cert = Date::excelToDateTimeObject($num_entero_ultima_cert);
                    $ult_cert = $fecha_ult_cert->format('Y-m-d');

                    $fechaAsignacion = null;

                    if (!empty($item[17]) && is_numeric($item[17])) {
                        $fechaNumero = intval($item[17]);
                        $fechaAsignacion = Date::excelToDateTimeObject($fechaNumero)->format('Y-m-d');
                    } else {
                        $fechaAsignacion = null;
                    }

                    if ($item[1] != null && $item[0] != null) {
                        $asignada = [
                            'nombre_lugar' => $item[6],
                            'direccion' => $item[10],
                            'departamento' => $item[7],
                            'localidad' => $item[8],
                            'contrato' => $item[1],
                            'telefono' => $item[12],
                            'tipo_solicitud' => $item[4],
                            'consecutivo_ruta' => $item[11],
                            'email' => "",
                            'emailCc' => "",
                            'latitud' => null,
                            'longitud' => null,
                            'id_cliente' => 13776,
                            'fecha_ult_cert' => $ult_cert,
                            'vence' => $vence,
                            'categoria' => $item[14],
                            'estado_producto' => $item[19],
                            'estado_corte' => $item[20],
                            'ult_comentario' => $item[21],
                            'orden' => $item[0],
                            'producto' => $item[2],
                            'numero_solicitud' => $item[3],
                            'observacion_solicitud' => $item[18],
                            'tipo_trabajo' => $item[16],
                            'sector_operativo' => $item[9],
                            'unidad_operativa' => $item[15],
                            'contratista' => "E&C INGENIERIA S.A.S",
                            'fecha_asignacion' => $fechaAsignacion,
                            'fecha_maximaEntrega' => $vence,
                            'NIT_CC' => $item[5],
                            'medidor' => $item[13],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $asignadas[] = $asignada;
                        Asignadas::insert($asignadas);
                        $asignadas = [];
                    }
                }
            }
        }
        unset($array);
        $this->eraseFile($response);
        return redirect()->route('cargues.load')->with('success', 'Datos cargados correctamente.');
    }

    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|mimes:xls'
        ], [
            'archivo.mimes' => 'El archivo debe ser de tipo xls.'
        ]);

        if ($validator->fails()) {
            return $validator;
        }

        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            $nombreArchivo = $archivo->getClientOriginalName();
            $ruta_archivo = $archivo->move(public_path('uploads'), $nombreArchivo);
            return $ruta_archivo->getPathname();
        }
    }

    public function eraseFile($ruta_archivo)
    {
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
    }


    public function readExcel($spreadsheet): array
    {
        $data = [];

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($row = 1; $row <= $highestRow; ++$row) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                    $value = $sheet->getCell([$col, $row])->getValue();
                    $rowData[] = $value;
                }
                if (!empty(array_filter($rowData))) {
                    $data[] = $rowData;
                }
            }
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            throw new \Exception("Error leyendo el archivo: " . $e->getMessage());
        }

        return $data;
    }

    public function receptionLoad()
    {
        return view('cargues.loadReception');
    }

    public function receptionStore(Request $request)
    {
        set_time_limit(400);

        $response = $this->uploadFile($request);

        if (is_object($response)) {
            return redirect()->route('load.receptionLoad')->with('error', $response->errors()->first());
        }

        $spreadsheet = IOFactory::load($response);

        $array = $this->readExcel($spreadsheet);

        $skipFirstRow = false;
        $chunkSize = 1000;

        $chunks = array_chunk($array, $chunkSize);

        $arrayTypeResult = [
            'Certificada' => ['.CERTIFICADA'],
            'CERTIFICADA CON NOVEDADES' => ['CERTIFICADA CON NOVEDADES'],
            'INSPECCIONADA CON DEFECTO CRITICO VALLE' => ['.INSPECCIONADA CON DEFECTO CRITICO VALLE'],
            'INSPECCIONADA CON DEFECTO NO CRITICO VALLE' => ['.INSPECCIONADA CON DEFECTO NO CRITICO VALLE'],

            // VNE
            'Aplazado por el usuario' => ['APLAZADO POR EL USUARIO.'],
            'Casa sola' => ['CASA SOLA', 'CASA SOLA.'],
            'Certificada por OIA externo' => ['CERTIFICADA POR OIA EXTERNO'],
            'Direccion no encontrada' => ['.DIRECCION NO ENCONTRADA'],
            'MEDIDOR FRENADO' => ['MEDIDOR FRENADO.'],
            'Medidor no existe' => ['MEDIDOR NO EXISTE.'],
            'Menor de edad' => ['MENOR DE EDAD.'],
            'No esta el encargado' => ['NO ESTA EL ENCARGADO', 'NO ESTÁ EL ENCARGADO.'],
            'Novedad bloqueante' => ['NOVEDAD BLOQUEANTE.'],
            'Predio desocupado' => ['PREDIO DESOCUPADO.'],
            'Predio en construccion' => ['.PREDIO EN CONSTRUCCION'],
            'Programada' => ['PROGRAMADA.'],
            'Suspendido por cartera' => ['SUSPENDIDO POR CARTERA.'],
            'Usuario no autoriza' => ['USUARIO NO AUTORIZA.'],
        ];

        foreach ($chunks as $chunk) {
            foreach ($chunk as $item) {
                if ($skipFirstRow === false) {
                    $skipFirstRow = true;
                    continue;
                }

                $isInArray = false;
                foreach ($arrayTypeResult as $key => $values) {
                    if (in_array($item[7], $values)) {
                        $isInArray = true;
                        break; // Salimos del bucle si encontramos coincidencia
                    }
                }

                $receptions = [];
                if ($isInArray) {

                    if ($item[7] === ".CERTIFICADA CON NOVEDADES" || $item[7] === ".CERTIFICADA") {
                        $estadoRecepcion = 1;
                    } else {
                        $estadoRecepcion = 2;
                    }

                    $estadoVne = null;
                    foreach ($arrayTypeResult as $key => $tipos) {
                        foreach ($tipos as $tipo) {
                            if ($item[7] === $tipo) {
                                $statusVneQuery = TblEstadosVne::where('estado_vne', $key)->first();
                                if ($statusVneQuery  != null) {
                                    $estadoVne = $statusVneQuery->id;

                                    $timestamp = ($item[13] - 25569) * 86400;

                                    $fechaLegible = gmdate('d-M-y H:i', $timestamp);

                                    $fechaLegible = strtolower($fechaLegible);


                                    $fechaPartes = explode(" ", $fechaLegible);

                                    // consultamos el nombre del operario con la cedula
                                    $queryInsp = tbl_insp_cali::where('cedula', $item[1])->first();

                                    if ($queryInsp != null) {
                                        $nombreInsp = $queryInsp->nombres . " " . $queryInsp->apellidos;
                                    } else {
                                        $nombreInsp = "";
                                    }

                                    $comObservacion =   "Inps: " . $nombreInsp .
                                        " | Causa: " . $statusVneQuery->estado_vne .
                                        " | Día: " . $fechaPartes[0] .
                                        " | Hora: " . $fechaPartes[1] .
                                        " | Obs: " . $item[6];

                                    // consultamos la tabla de detalle para no guardar registros repetidos
                                    $recepcionVneDetalle = TblRecepcionVneDetalle::where('ordenTrabajo', $item[11])
                                        ->where('idVne', $estadoVne)
                                        ->where('ccOperario', $item[1])
                                        ->where('comObservacion', $comObservacion)
                                        ->first();

                                    if ($recepcionVneDetalle == null) {
                                        // guardamos en la tabla de repecion vne detalle
                                        TblRecepcionVneDetalle::insert([
                                            'ordenTrabajo' => $item[11],
                                            'idVne' => $estadoVne,
                                            'ccOperario' => $item[1],
                                            'comObservacion' => $comObservacion,
                                            'created_at' => now(),
                                        ]);
                                    }
                                }
                            }
                        }
                    }

                    $contrato = explode(":", $item[0]);
                    if (isset($contrato[1])) {
                        $contratoFila = $contrato[1];
                    } else {
                        $contratoFila = $contrato[0];
                    }

                    $asiggnedQuery = Asignadas::where('orden',  $item[11]);

                    if ($item[12] != null) {
                        $asiggnedQuery->orWhere('orden_solicitud_externa', $item[12]);
                    }

                    $asiggnedQuery->orWhere('numero_solicitud', $item[54]);

                    $asignadas = $asiggnedQuery->first();

                    if ($asignadas) {
                        $tipo = "Existe efe";
                    } else {
                        $tipo = "No existe efe";
                    }

                    $receptions[] = [
                        'ordenTrabajo' => $item[11],
                        'ordenExterna' => $item[12],
                        'ccOperario' => $item[1],
                        'numeroSolicitud' => $item[54],
                        'contrato' => $contratoFila,
                        'tipo' => $tipo,
                        'idVne' =>  $estadoVne,
                        'direccion' => $item[48],
                        'numActa' => $item[4],
                        'estadoRecepcion' => $estadoRecepcion,
                        'created_at' => now(),
                    ];

                    // consulamos en la tabla de recepcionj si ya existe un registro con esa misma orden de trabajo
                    $recepcionQuery = TblRecepcion::where('ordenTrabajo', $item[11])->first();
                    if ($recepcionQuery == null) {
                        TblRecepcion::insert($receptions);
                    } else {
                        $recepcionQuery->update([
                            'ordenExterna' => $item[12],
                            'ccOperario' => $item[1],
                            'numeroSolicitud' => $item[54],
                            'contrato' => $contratoFila,
                            'tipo' => $tipo,
                            'idVne' => $estadoVne,
                            'direccion' => $item[48],
                            'numActa' => $item[4],
                            'estadoRecepcion' => $estadoRecepcion,
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
        unset($array);
        $this->eraseFile($response);
        return redirect()->route('load.receptionLoad')->with('success', 'Datos cargados correctamente.');
    }

    public function getReceptions()
    {
        $inspectors = tbl_insp_cali::all();
        return view('gestion.reception', compact('inspectors'));
    }

    public function getDataReception(Request $request)
    {
        $porPagina = 100; // Cantidad de registros por página
        $pagina = $request->input('pagina', 1); // Obtener el número de página de la solicitud
        $offset = ($pagina - 1) * $porPagina;

        $datos = TblRecepcion::select('*')
            ->skip($offset)
            ->take($porPagina)
            ->get();

        $totalResults = TblRecepcion::select('id')->count();

        $datosConIndice = $datos->map(function ($item, $index) use ($offset) {

            // consultamos la tabla de inspectores cali para obtener el codigo del inspector
            $inspector = tbl_insp_cali::where('cedula', $item->ccOperario)->first();

            if ($inspector) {
                $codigoTecnico = $inspector->id;
            } else {
                $codigoTecnico = "";
            }

            return [
                0 => $index + 1 + $offset,
                1 => $item->ordenTrabajo,
                2 => $item->ordenExterna,
                3 => $item->numeroSolicitud,
                4 => $item->contrato,
                5 => $item->direccion,
                6 => $codigoTecnico,
                7 => $item->tipo,
                8 => $item->estadoRecepcion,
                9 => explode(" ", $item->created_at)[0],
                10 => $item->numActa
            ];
        });

        return response()->json(
            [
                'data' => $datosConIndice,
                'totalResults' => $totalResults
            ]
        );
    }

    public function filterData(Request $request)
    {
        if (isset($request->all()['datosFormulario'])) {
            $data = $request->all()['datosFormulario'];
        } else {
            $data = [];
        }

        $query = TblRecepcion::select('*');

        if (!empty($data)) {
            foreach ($data as $key => $value) {

                $arrayValues = [];

                if (!is_array($value) && strpos($value, ',') !== false) {
                    $valueSeparate = explode(",", $value);
                    foreach ($valueSeparate as $value) {
                        $arrayValues[] = intval($value);
                    }
                } else {
                    $arrayValues[] = intval($value);
                }

                $operator = 'IN';

                if ($key == "direccion" || $key == "created_at") {
                    $operator = 'LIKE';
                    $values = ['%' . $value . '%'];
                } else if ($key == "ccOperario") {
                    $arrayInspectors = [];
                    foreach ($value as $val) {
                        $arrayInspectors[] = intval($val);
                        $inspector = tbl_insp_cali::where('id', $val)->first();
                        $arrayValues[] = $inspector->cedula;
                    }
                } else if ($key == "tipo" || $key == "estadoRecepcion") {
                    $operator = '=';
                    $values = [$value];
                }

                if ($operator === 'IN') {
                    $query->whereIn($key, $arrayValues);
                } else if ($operator === 'LIKE') {
                    $query->where($key, $operator, $values[0]);
                } else {
                    $query->where($key, $operator, $values[0]);
                }
            }
        }

        $porPagina = 100;
        $pagina = $request->input('pagina', 1);
        $offset = ($pagina - 1) * $porPagina;

        // contamos cuantos registros tenemos en total
        $totalResults = $query->count();

        // obtenemos los registros por paginas
        $sqlReceptiopn = $query->skip($offset)
            ->take($porPagina)
            ->get();

        $datosConIndice = $sqlReceptiopn->map(function ($item, $index) use ($offset) {

            // consultamos la tabla de inspectores cali para obtener el codigo del inspector
            $inspector = tbl_insp_cali::where('cedula', $item->ccOperario)->first();

            if ($inspector) {
                $codigoTecnico = $inspector->id;
            } else {
                $codigoTecnico = "";
            }

            return [
                0 => $index + 1 + $offset,
                1 => $item->ordenTrabajo,
                2 => $item->ordenExterna,
                3 => $item->numeroSolicitud,
                4 => $item->contrato,
                5 => $item->direccion,
                6 => $codigoTecnico,
                7 => $item->tipo,
                8 => $item->estadoRecepcion,
                9 => explode(" ", $item->created_at)[0],
                10 => $item->numActa
            ];
        });

        return response()->json(
            [
                'data' => $datosConIndice,
                'totalResults' => $totalResults
            ]
        );
    }

    public function storeClosed(Request $request)
    {
        $response = AsignadasController::uploadFile($request);

        if (is_object($response)) {
            return redirect()->route('cargues.load')->with('error', $response->errors()->first());
        }

        $spreadsheet = IOFactory::load($response);

        $array = AsignadasController::readExcel($spreadsheet);

        $skipFirstRow = false;
        $chunkSize = 1000;

        $chunks = array_chunk($array, $chunkSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $item) {
                if ($skipFirstRow === false) {
                    $skipFirstRow = true;
                    continue;
                }

                // consultamos en la tabla de asigandas con el numero de orden del excel
                $queryAsignadas = asignadas::where('orden_solicitud_externa', $item[0])
                    ->orWhere('contrato', $item[1])
                    ->orWhere('orden', $item[0])
                    ->where('status', 1)
                    ->first();

                if ($queryAsignadas != null) {

                    if ($queryAsignadas->orden_solicitud_externa != null) {
                        $diaIngreso = $queryAsignadas->fecha_reasignacion_externa;
                    } else {
                        $diaIngreso = explode(" ", $queryAsignadas->created_at)[0];
                    }

                    $timestamp = ($item[4] - 25569) * 86400;
                    $fechaLegible = gmdate('d-M-y', $timestamp);
                    $fechaLegible = strtolower($fechaLegible);

                    // hacemos la resta de los dias para poner los dias en proceso
                    $fechaLegalizacion = DateTime::createFromFormat('d-M-y', $fechaLegible)->format('d-m-Y');
                    $fechaDiaIngeso = DateTime::createFromFormat('Y-m-d', $diaIngreso)->format('d-m-Y');

                    $fecha1 = new DateTime($fechaLegalizacion);
                    $fecha2 = new DateTime($fechaDiaIngeso);

                    $diferencia = $fecha2->diff($fecha1);

                    $diasDiferencia = $diferencia->days;

                    if (
                        $queryAsignadas->orden_trabajo_cerrada != null && $queryAsignadas->orden_trabajo_cerrada == $item[0] &&
                        $queryAsignadas->contrato_cerrada == $item[1] && $queryAsignadas->producto_cerrada == $item[2] &&
                        $queryAsignadas->tipo_trabajo_cerrada == $item[3] && $queryAsignadas->fecha_legalizacion == $fechaLegible &&
                        $queryAsignadas->comentario_legalizacion == $item[5] && $queryAsignadas->cod_causal == $item[6] &&
                        $queryAsignadas->des_causal == $item[7] && $queryAsignadas->consecutivo == $item[8] &&
                        $queryAsignadas->dias_proceso == $diasDiferencia
                    ) {
                        continue;
                    }

                    $queryAsignadas->update([
                        'orden_trabajo_cerrada' => $item[0],
                        'contrato_cerrada' => $item[1],
                        'producto_cerrada' => $item[2],
                        'tipo_trabajo_cerrada' => $item[3],
                        'fecha_legalizacion' => $fechaLegible,
                        'comentario_legalizacion' => $item[5],
                        'cod_causal' => $item[6],
                        'des_causal' => $item[7],
                        'consecutivo' => $item[8],
                        'dias_proceso' => $diasDiferencia,
                        'status' => 0
                    ]);
                }
            }
        }

        unset($array);
        $this->eraseFile($response);
        return redirect()->route('cargues.load')->with('success', 'Datos cargados correctamente.');
    }
}
