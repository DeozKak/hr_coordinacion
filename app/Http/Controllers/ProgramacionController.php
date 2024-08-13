<?php

namespace App\Http\Controllers;

use App\Models\tbl_programacion_usuario;
use App\Models\tbl_programacion_base;
use App\Models\tbl_insp_cali;
use App\Models\tbl_programacion_contrato;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Dotenv\Exception\ValidationException;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;


class ProgramacionController extends Controller
{
    public function index()
    {

        if (Auth::user()->haspermissionTo('ver_programacion')) {
            $datos = tbl_programacion_usuario::where('finished', 1)->with('usuario')->get();
            $temp = tbl_programacion_usuario::where('finished', 0)->where('id_usuario', Auth::id())->first();
        } else {
            $datos = tbl_programacion_usuario::where('finished', 1)->where('id_usuario', Auth::id())->with('usuario')->get();
            $temp = tbl_programacion_usuario::where('finished', 0)->where('id_usuario', Auth::id())->first();
        }

        if (!is_null($temp)) {
            session()->flash('warning', 'Ya tienes una tabla de programación en curso ¿Deseas continuar?');

            return view('programacion.index', compact('datos', 'temp'));
        }

        return view('programacion.index', compact('datos'));
    }

    public function create()
    {
        $programacion = tbl_programacion_usuario::where('finished', 0)->where('id_usuario', Auth::id())->first();
        if (is_null($programacion)) {
            $fechaActual = Carbon::now();
            $soloFecha = $fechaActual->format('Y-m-d');
            $programacion =  new tbl_programacion_usuario;
            $programacion->nombre = "Programación " . $soloFecha;
            $programacion->id_usuario = Auth::id();
            $programacion->save();

            $tecnicos = tbl_insp_cali::select('apellidos', 'nombres')
                ->where('state', 1)
                ->orderBy('apellidos') // Ordenar por apellidos ascendente
                ->get();

            $user = Auth::user();

            return view('programacion.create', compact('tecnicos', 'user', 'programacion'));
        } else {
            $tecnicos = tbl_insp_cali::select('apellidos', 'nombres')
                ->where('state', 1)
                ->orderBy('apellidos') // Ordenar por apellidos ascendente
                ->get();

            $user = Auth::user();

            return view('programacion.create', compact('tecnicos', 'user', 'programacion'));
        }
    }

    public function show($id)
    {
        $programacion = tbl_programacion_usuario::find($id);
        $tabla = tbl_programacion_contrato::where('id_programacion', $id)->get();

        $programacion->finished = 0;
        $programacion->save();


        $user = Auth::user();

        $tecnicos = tbl_insp_cali::select('apellidos', 'nombres')
            ->where('state', 1)
            ->orderBy('apellidos') // Ordenar por apellidos ascendente
            ->get();

        return view('programacion.create', compact('tecnicos', 'user', 'programacion', 'tabla'));
    }

    public function base(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:xls,xlsx',
            ], [
                'archivo.required' => 'El campo archivo es obligatorio.',
                'archivo.file' => 'El valor debe ser un archivo.',
                'archivo.mimes' => 'El archivo debe ser de tipo XLS o XLSX.',
            ]);

            $archivo = $request->file('archivo');
            $spreadsheet = IOFactory::load($archivo);
            $worksheet = $spreadsheet->getActiveSheet();
            $indicador = $this->validacion($worksheet);

            if (!$indicador) {
                return response()->json(['errors' => 'El archivo no cumple los criterios requeridos'], 422);
            }

            $valor = $this->insercion($worksheet);


            if ($valor === true) {
                return response()->json(['message' => 'Archivo subido exitosamente']);
            } else {
                return response()->json(['errors' => 'Error al subir el archivo'], 422);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e], 422);
        }
    }

    public function validacion($worksheet)
    {
        $indicador = true;
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $columna) {
            switch ($columna) {
                case 'A':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "NUMERO_ORDEN") ? true : false;
                    break;
                case 'B':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "CONTRATO") ? true : false;
                    break;
                case 'C':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "DESC_ESTADO_PROD") ? true : false;
                    break;
                case 'D':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "NOMBRE") ? true : false;
                    break;
                case 'E':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "DESC_LOCALIDAD") ? true : false;
                    break;
                case 'F':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "BARRIO") ? true : false;
                    break;
                case 'G':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "DIRECCION") ? true : false;
                    break;
                case 'H':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "NOM_CATE") ? true : false;
                    break;
                case 'I':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "ID_TIPO_TRABAJO") ? true : false;
                    break;
            }
        }
        return $indicador;
    }

    public function insercion($worksheet)
    {
        $registros = []; // Array para almacenar los registros en lotes
        $tamañoLote = 2000; // Puedes ajustar el tamaño del lote según tus necesidades

        DB::beginTransaction(); // Iniciar una transacción

        try {
            foreach ($worksheet->getRowIterator() as $row) {
                if ($row->getRowIndex() === 1) {
                    continue; // Saltar la primera fila (encabezados)
                }
                $rowData = [];
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $columna) {
                    $valorCelda = $worksheet->getCell($columna . $row->getRowIndex())->getValue();

                    switch ($columna) {
                        case 'A':
                            $rowData["NUMERO_ORDEN"] = $valorCelda;
                            break;
                        case 'B':
                            $rowData["CONTRATO"] = $valorCelda;
                            break;
                        case 'C':
                            $rowData["DESC_ESTADO_PROD"] = $valorCelda;
                            break;
                        case 'D':
                            $rowData["NOMBRE"] = $valorCelda;
                            break;
                        case 'E':
                            $rowData["DESC_LOCALIDAD"] = $valorCelda;
                            break;
                        case 'F':
                            $rowData["BARRIO"] = $valorCelda;
                            break;
                        case 'G':
                            $rowData["DIRECCION"] = $valorCelda;
                            break;
                        case 'H':
                            $rowData["NOM_CATE"] = $valorCelda;
                            break;
                        case 'I':
                            $rowData["ID_TIPO_TRABAJO"] = $valorCelda;
                            break;
                    }
                }
                $registros[] = $rowData;

                if (count($registros) >= $tamañoLote) {
                    $this->insertarLoteConVerificacionDuplicados($registros);
                    $registros = [];
                }
            }
            // Insertar registros restantes (si los hay)
            if (!empty($registros)) {
                $this->insertarLoteConVerificacionDuplicados($registros);
            }

            DB::commit(); // Confirmar la transacción si todo tiene éxito
            return true;
        } catch (QueryException $e) {
            DB::rollback(); // Revertir la transacción si ocurre un error
            Log::error("Error al insertar datos: " . $e->getMessage()); // Registrar el error para depuración
            return false;
        }
    }

    private function insertarLoteConVerificacionDuplicados($registros)
    {
        $numerosOrden = array_column($registros, 'NUMERO_ORDEN');
        $ordenesExistentes = tbl_programacion_base::whereIn('NUMERO_ORDEN', $numerosOrden)->pluck('NUMERO_ORDEN')->toArray();

        $registrosAInsertar = array_filter($registros, function ($registro) use ($ordenesExistentes) {
            return !in_array($registro['NUMERO_ORDEN'], $ordenesExistentes);
        });

        if (!empty($registrosAInsertar)) {
            tbl_programacion_base::insert($registrosAInsertar);
        }
    }

    public function busqueda($contrato)
    {
        // Validación numérica
        if (!is_numeric($contrato)) {
            return null; // O puedes devolver una respuesta adecuada, como un JSON vacío
        }

        if ($contrato == '') {
            return null;
        }

        $datos = tbl_programacion_base::where('CONTRATO', $contrato)->first();

        if ($datos) {
            return response()->json($datos);
        } else {
            return response()->json(['errors' => 'No se encontraron registros'], 422);
        }
    }

    public function store(Request $request)
    {

        $data = $request->data;

        $exist = tbl_programacion_contrato::where('CONTRATO', $request->data[1])
            ->where('ORDEN_TRABAJO', $request->data[6])
            ->where('TIPO_TRABAJO', $request->data[2])
            ->exists();

        if ($exist) {
            return response()->json(['error' => 'Ya existe una programación con estos datos']);
        }

        try {
            $programacion = new tbl_programacion_contrato();
            $programacion->CONTRATO = $request->data[1];
            $programacion->TIPO_TRABAJO = $request->data[2];
            $programacion->FECHA = $request->data[3];
            $programacion->CELULAR = $request->data[4];
            $programacion->NOMBRE_USUARIO = $request->data[5];
            $programacion->ORDEN_TRABAJO = $request->data[6];
            $programacion->DIRECCION = $request->data[7];
            $programacion->BARRIO = $request->data[8];
            $programacion->CIUDAD = $request->data[9];
            $programacion->ACTIVA = $request->data[10];
            $programacion->SUSPENDIDO = $request->data[11];
            $programacion->CATEGORIA = $request->data[12];
            $programacion->FECHA_AGENDAMIENTO = $request->data[13];
            $programacion->OBSERVACIONES = $request->data[14];
            $programacion->PORQUE_PROGRAMO = $request->data[15];
            $programacion->TECNICO = $request->data[16];
            $programacion->HORA_INICIO = $request->data[17];
            $programacion->HORA_FINAL = $request->data[18];
            $programacion->id_programacion = $request->tabla;
            $programacion->save();
        } catch (QueryException $e) {
            return response()->json(['error' => $e]);
        }


        return response()->json(['message' => 'Registro guardado correctamente', 'id' => $programacion->id]);
    }

    public function update($id, Request $request)
    {

        try {
            $programacion = tbl_programacion_contrato::find($id);

            $campo = $request->propiedad;

            $programacion->$campo = $request->valor;
            $programacion->save();

            return response()->json(['message' => 'Registro actualizado correctamente']);
        } catch (QueryException $e) {
            return response()->json(['error' => $e]);
        }
    }

    public function destroy(Request $resquest)
    {

        try {
            $id = $resquest->data;
            $programacion = tbl_programacion_contrato::find($id);
            $programacion->delete();
        } catch (QueryException $e) {
            return response()->json(['error' => $e]);
        }
        return response()->json(['message' => 'Registro eliminado correctamente']);
    }

    public function erase($id)
    {
        $programacion = tbl_programacion_usuario::find($id);
        $contratos = tbl_programacion_contrato::where('id_programacion', $id)->get();
        $contratos->each->delete();
        $programacion->delete();

        return response()->json(['message' => 'Programación eliminada correctamente']);
    }

    public function finish($id)
    {
        try {
            $programacion = tbl_programacion_usuario::find($id);
            $programacion->finished = 1;
            $programacion->save();

            $programadas = tbl_programacion_contrato::where('id_programacion', $id)->get();

            foreach ($programadas as $programada) {

                if($programada->CELULAR == null || $programada->CELULAR == '' || $programada->mensaje == 1){  
                    continue;
                }
                // Establecer la zona horaria a Colombia
                date_default_timezone_set('America/Bogota');
                
                $horaActual = date('H'); // Obtener la hora actual en formato de 24 horas

                if ($horaActual >= 5 && $horaActual < 12) {
                    $saludo = "Buenos días";
                } elseif ($horaActual >= 12 && $horaActual < 19) {
                    $saludo = "Buenas tardes";
                } else {
                    $saludo = "Buenas noches";
                }

                // Convertir la cadena de fecha a un objeto Carbon
                $fecha_carbon = Carbon::createFromFormat('Y-m-d', $programada->FECHA_AGENDAMIENTO);

                // Formatear la fecha en español
                // locale es importante para obtener los nombres de los meses en español
                $fecha_formateada = $fecha_carbon->locale('es')->isoFormat('D [de] MMMM [de] YYYY');

                $bodyData = [
                    'typing_time' => 0,
                    'to' => '57' . $programada->CELULAR,
                    'body' => $saludo . ', Sr./Sra. ' . $programada->NOMBRE_USUARIO . '. 👋' .
                        'Le informamos que la inspección de la red de gas en su predio está programada para el día ' . $fecha_formateada . '  entre las ' . $programada->HORA_INICIO . ' a ' . $programada->HORA_FINAL . '.  El inspector a cargo será ' . $programada->TECNICO . '. 👷‍♂️' .
                        'Agradecemos su atención y colaboración. 🙏'
                ];

                $client = new Client();
                $response = $client->request('POST', 'https://gate.whapi.cloud/messages/text', [
                    'json' => $bodyData,
                    'headers' => [
                        'accept' => 'application/json',
                        'authorization' => 'Bearer bGBktWXeKxgX1syNGKtT8al4rfZHRemt',
                        'content-type' => 'application/json',
                    ],
                ]);

                $programada->mensaje = 1;
                $programada->save();
            
            }
        } catch (QueryException $e) {
            return response()->json(['error' => $e]);
        }
        session()->flash('success', 'Programación finalizada correctamente');
        return response()->json(['ok' => 'Programación finalizada correctamente']);
    }

    public function detalles()
    {

        return view('programacion.ver');
    }

    public function agendamiento(Request $request)
    {

        $request->validate([
            'fecha' => 'required',
        ]);

        $fecha = $request->fecha;

        $columnasTabla = Schema::getColumnListing('tbl_programacion_contratos'); // Obtener todas las columnas de la tabla

        $columnasAExcluir = ['updated_at', 'created_at']; // Columnas que deseas excluir
        $columnasAIncluir = array_diff($columnasTabla, $columnasAExcluir); // Calcula las columnas a incluir

        $busqueda = tbl_programacion_contrato::where('FECHA_AGENDAMIENTO', $fecha)
            ->whereHas('state', function ($query) {
                $query->where('finished', 1);
            })
            ->select($columnasAIncluir)
            ->get();


        return response()->json([
            'data' => $busqueda,
            'columnas' => $columnasAIncluir
        ]);
    }
}
