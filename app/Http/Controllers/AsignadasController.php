<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\asignadas;
use App\Models\tbl_insp_cali;
use App\Models\TblRecepcion;
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
                            ->update([
                                'orden_solicitud_externa' => $item[0],
                                'tipo_solicitud_externa' => $item[16],
                                'fecha_solicitud_externa' => $fechaSolExt,
                                'obervacion_externa' => $item[18],
                                'fecha_reasignacion_externa' => date('Y-m-d')
                            ]);
                    }
                } else {
                    // Si no se encuentra en la base de datos, insertar
                    $num_entero = intval($item[23]);
                    $fecha_vence = Date::excelToDateTimeObject($num_entero);
                    $vence = $fecha_vence->format('Y-m-d');

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
                            'vence' => $vence,
                            'categoria' => $item[14],
                            'estado_producto' => $item[19],
                            'estado_corte' => $item[20],
                            'orden' => $item[0],
                            'orden_externa' => null,
                            'producto' => $item[2],
                            'numero_solicitud' => $item[3],
                            'observacion_solicitud' => $item[18],
                            'tipo_trabajo' => $item[16],
                            'sector_operativo' => $item[9],
                            'unidad_operativa' => $item[15],
                            'contratista' => "E&C INGENIERIA S.A.S",
                            'fecha_asignacion' => $fechaAsignacion,
                            'fecha_externa' => null,
                            'fecha_maximaEntrega' => $vence,
                            'NIT_CC' => $item[5],
                            'medidor' => $item[13],
                            'created_at' => now(),
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

        set_time_limit(300);

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
            '.CERTIFICADA',
            'CERTIFICADA CON NOVEDADES',
            '.INSPECCIONADA CON DEFECTO CRITICO VALLE',
            '.INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
        ];

        foreach ($chunks as $chunk) {
            foreach ($chunk as $item) {
                if ($skipFirstRow === false) {
                    $skipFirstRow = true;
                    continue;
                }
                $receptions = [];
                if (in_array($item[7], $arrayTypeResult)) {

                    if ($item[7] === '.CERTIFICADA CON NOVEDADES' || $item[7] === ".CERTIFICADA") {
                        $estadoRecepcion = 1;
                    } else {
                        $estadoRecepcion = 2;
                    }

                    $contrato = explode(":", $item[0]);
                    if (isset($contrato[1])) {
                        $contratoFila = $contrato[1];
                    } else {
                        $contratoFila = $contrato[0];
                    }

                    $receptions[] = [
                        'ordenTrabajo' => $item[11],
                        'ordenExterna' => $item[12],
                        'ccOperario' => $item[1],
                        'numeroSolicitud' => $item[54],
                        'contrato' => $contratoFila,
                        'direccion' => $item[48],
                        'numActa' => $item[4],
                        'numActa' => $item[4],
                        'estadoRecepcion' => $estadoRecepcion,
                        'created_at' => now(),
                    ];
                    TblRecepcion::insert($receptions);
                }
            }
        }
        unset($array);
        $this->eraseFile($response);
        return redirect()->route('load.receptionLoad')->with('success', 'Datos cargados correctamente.');
    }

    public function getReceptions(Request $request)
    {
        return view('gestion.reception');
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

        $datosConIndice = $datos->map(function ($item, $index) use ($offset) {

            // consultamos la tabla de asignadas
            $asignadasQuery = Asignadas::where('orden', $item->ordenTrabajo);

            // Si `$item->ordenExterna` no es `null`, agrega la condición `orWhere`
            if ($item->ordenExterna !== null) {
                $asignadasQuery->orWhere('orden_solicitud_externa', $item->ordenExterna);
            }

            // Agrega la condición `orWhere` para `numero_solicitud`
            $asignadasQuery->orWhere('numero_solicitud', $item->numeroSolicitud);

            // Ejecuta la consulta y obtiene el primer resultado
            $asignadas = $asignadasQuery->first();

            if ($asignadas) {
                $tipo = "Existe efe";
            } else {
                $tipo = "No existe efe";
            }

            // consultamos la tabla de inspectores cali para obtener el codigo del inspector
            $inspector = tbl_insp_cali::where('cedula', $item->ccOperario)->first();

            if($inspector){
                $codigoTecnico = $inspector->id;
            }else{
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
                7 => $tipo,
                8 => $item->estadoRecepcion,
                9 => explode(" ", $item->created_at)[0],
                10 => $item->numActa
            ];
        });

        return response()->json(
            [
                'data' => $datosConIndice,
            ]
        );
    }
}
