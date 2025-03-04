<?php

namespace App\Http\Controllers;

use App\Models\tbl_programacion_usuario;
use App\Models\tbl_programacion_base;
use App\Models\tbl_insp_cali;
use App\Models\tbl_programacion_contrato;
use App\Models\User;
use App\Models\Movilidad;
use App\Notifications\Programada;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Dotenv\Exception\ValidationException;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Jobs\CorreoProgramacion;

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

            $tecnicos = tbl_insp_cali::select('id', 'apellidos', 'nombres')
                ->where('state', 1)
                ->orderBy('apellidos') // Ordenar por apellidos ascendente
                ->get();

            $user = Auth::user();

            return view('programacion.create', compact('tecnicos', 'user', 'programacion'));
        } else {
            return $this->index();
            /*  $tecnicos = tbl_insp_cali::select('id', 'apellidos', 'nombres')
                ->where('state', 1)
                ->orderBy('apellidos') // Ordenar por apellidos ascendente
                ->get();

            $user = Auth::user(); */
        }
    }

    public function show(Request $request, $id)
    {
        $action = $request->query('action');

        $programacion = tbl_programacion_usuario::find($id);
        $tabla = tbl_programacion_contrato::where('id_programacion', $id)->get();

        if ($action === 'edit') {
            if (auth()->user()->haspermissionTo('generar_programacion')) {
                $programacion->finished = 0;
                $programacion->save();
            } else {
                session()->flash('error', 'Acción no autorizada.');
                return redirect()->route('programacion.index');
            }
        }

        $user = User::find($programacion->id_usuario);



        $tecnicos = tbl_insp_cali::select('id', 'apellidos', 'nombres')
            ->where('state', 1)
            ->orderBy('apellidos') // Ordenar por apellidos ascendente
            ->get();

        if ($action === 'view') {

            $view = true;
            return view('programacion.create', compact('tecnicos', 'user', 'programacion', 'tabla', 'view', 'user'));
        }
        if ($action === 'edit') {

            return view('programacion.create', compact('tecnicos', 'user', 'programacion', 'tabla', 'user'));
        }
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
            Log::error($e);
            return response()->json(['errors' => $e], 422);
        }
    }


    public function validacion($worksheet)
    {
        $indicador = true;
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'] as $columna) {
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
                case 'J':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Código Técnico") ? true : false;
                    break;
                case 'K':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Fecha asignación") ? true : false;
                    break;
                case 'L':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Estado recepción") ? true : false;
                    break;
                case 'M':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Fecha recepción") ? true : false;
                    break;
            }
        }
        return $indicador;
    }

    public function insercion($worksheet)
    {
        $registros = []; // Array para almacenar los registros en lotes
        $tamañoLote = 2000; // Puedes ajustar el tamaño del lote según tus necesidades

        tbl_programacion_base::truncate();

        DB::beginTransaction(); // Iniciar una transacción

        try {
            foreach ($worksheet->getRowIterator() as $row) {
                if ($row->getRowIndex() === 1) {
                    continue; // Saltar la primera fila (encabezados)
                }
                $rowData = [];
                $filaVacia = true;
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'] as $columna) {
                    $valorCelda = $worksheet->getCell($columna . $row->getRowIndex())->getValue();

                    // Verificar si la celda tiene algún valor
                    if (!empty($valorCelda)) {
                        $filaVacia = false; // La fila no está vacía si al menos una celda tiene valor
                    }

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
                        case 'J':
                            $rowData["ID_TECNICO"] = $valorCelda;
                            break;
                        case 'K':
                            if ($valorCelda !== null || $valorCelda !== "0") { // Verificar si la celda tiene un valor
                                $excelTimestamp = (float) $valorCelda;
                                $fechaAsignacion = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelTimestamp);
                                $rowData["FECHA_ASIGNACION"] = $fechaAsignacion->format('Y-m-d');
                            } else {
                                $rowData["FECHA_ASIGNACION"] = null; // O cualquier otro valor predeterminado que desees
                            }
                            break;
                        case 'L':
                            $rowData["ESTADO_RECEPCION"] = $valorCelda;
                            break;
                        case 'M':
                            if ($valorCelda !== null || $valorCelda !== "0") { // Verificar si la celda tiene un valor
                                $excelTimestamp = (float) $valorCelda;
                                $fechaAsignacion = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelTimestamp);
                                $rowData["FECHA_RECEPCION"] = $fechaAsignacion->format('Y-m-d');
                            } else {
                                $rowData["FECHA_RECEPCION"] = null; // O cualquier otro valor predeterminado que desees
                            }
                            break;
                    }
                }

                if ($filaVacia) {
                    continue; // Saltar la fila si está vacía
                }

                if ($rowData["ID_TECNICO"] !== null) {
                    if ($rowData["ID_TECNICO"] !== "0") {

                        //modificar el tecnico asignado de la base dependiendo de la base en excel
                        $programacion = tbl_programacion_contrato::where('ORDEN_TRABAJO', $rowData["NUMERO_ORDEN"])->where('CONTRATO', $rowData["CONTRATO"])->get();
                        if ($programacion->count() > 0) {
                            foreach ($programacion as $pro) {
                                try {
                                    $inspector = tbl_insp_cali::where('id', $rowData["ID_TECNICO"])->first();
                                    if ($pro->FECHA_AGENDAMIENTO >= date('Y-m-d')) {
                                        $pro->TECNICO = $rowData["ID_TECNICO"] . '. ' . $inspector->apellidos . ' ' . $inspector->nombres;
                                        $pro->save();
                                    }
                                } catch (\Throwable $th) {
                                    Log::error($th);
                                }
                            }
                        }
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
            throw $e;

            DB::rollback(); // Revertir la transacción si ocurre un error
            Log::error("Error al insertar datos: " . $e->getMessage()); // Registrar el error para depuración
            return false;
        }
    }

    private function insertarLoteConVerificacionDuplicados($registros)
    {
        // Insertar los nuevos registros
        tbl_programacion_base::insert($registros);
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
        if ($datos == null) {
            return null;
        }

        if ($datos?->ESTADO_RECEPCION !== null) {
            if ($datos->ESTADO_RECEPCION == '1' || $datos->ESTADO_RECEPCION == '2') {
                return response()->json(['errors' => 'El contrato ya ha sido ejecutado']);
            }
        }
        if ($datos) {
            $id_inspector = $datos->ID_TECNICO;

            $inspector = tbl_insp_cali::where('id', $datos->ID_TECNICO)->first();
            if ($inspector !== null) {
                // Modificar la propiedad ID_TECNICO con el resultado de la consulta
                $datos->ID_TECNICO = $id_inspector . '. ' . $inspector->apellidos . ' ' . $inspector->nombres;
            } else {
                $datos->ID_TECNICO = null;
            }
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
            ->first();
        if ($exist) {
            return response()->json([
                'exist' => 'Ya existe una programación con estos datos',
                'id' => $exist->id,
                'usuario' => $exist->PORQUE_PROGRAMO,
                'agendamiento' => $exist->FECHA_AGENDAMIENTO
            ]);
        }
        $cierres = [
            'CERTIFICADA',
            'CERTIFICADA CON NOVEDADES',
            'INSPECCIONADA CON DEFECTO CRITICO VALLE',
            'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
        ];

        $tipos_trabajo_rp = array("10444", "12161");
        $tipos_trabajo_sa = array("12163", "12164");

        if (in_array($request->data[2], $tipos_trabajo_rp)) {
            $tipo_trabajo = ["RP 10444", "RP 12161"];
        } elseif (in_array($request->data[2], $tipos_trabajo_sa)) {
            $tipo_trabajo = ["SA " . $request->data[2]];
        } elseif ($request->data[2] == "12162") {
            $tipo_trabajo = ["RN " . $request->data[2]];
        }
        $contrato = ':' . $request->data[1];
        $movilidad = Movilidad::select('NombreOperario', 'FechaRealInicio','Cierre1','TipoTarea')
            ->where('NroSitio',  $contrato)
            ->whereIn('TipoTarea', $tipo_trabajo)
            ->where('Grupo', 'INSP-VALLE')
            ->whereIn('Cierre1', $cierres)
            ->first();

        if ($movilidad) {
            if(in_array($movilidad->Cierre1, ['INSPECCIONADA CON DEFECTO CRITICO VALLE','INSPECCIONADA CON DEFECTO NO CRITICO VALLE','INSPECCIONADA CON DEFECTO CRITICO VALLE']) && $movilidad->TipoTarea === 'SA 12164'){
                
            }else{
            $fecha_completa = $movilidad->FechaRealInicio;
            $partes = explode(' ', $fecha_completa);
            $fecha = $partes[0];
            return response()->json([
                'movilidad' => 'Contrato ya ejecutado',
                'usuario' => $movilidad->NombreOperario,
                'agendamiento' => $fecha
                ]);
            }
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
            log::error($e);

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
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => $e]);
        }
        return response()->json(['message' => 'Registro actualizado correctamente']);
    }

    public function destroy(Request $request)
    {

        try {
            $id = $request->data;
            $programacion = tbl_programacion_contrato::find($id);
            $programacion->delete();
        } catch (QueryException $e) {
            Log::error($e);
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
            $user = User::find($programacion->id_usuario);
            $programadas = tbl_programacion_contrato::where('id_programacion', $id)->get();

            if ($programacion->mensaje == 0) {

                $programacion->mensaje = 1;
                $programacion->save();
                CorreoProgramacion::dispatch($user, $id);
            }

            foreach ($programadas as $programada) {

                if ($programada->CELULAR == null || $programada->CELULAR == '' || $programada->mensaje == 1) {
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

                //quitar numero al tecnico

                $tecnico = $programada->TECNICO;
                $tecnico_sin_numero = substr($tecnico, strpos($tecnico, ". ") + 2);

                /* $bodyData = [
                    'typing_time' => 0,
                    'to' => '57' . $programada->CELULAR,
                    'body' => $saludo . ', Sr./Sra. ' . $programada->NOMBRE_USUARIO . '. 👋' .
                        'Le informamos que la inspección de la red de gas en su predio está programada para el día ' . $fecha_formateada . '  entre las ' . $programada->HORA_INICIO . ' a ' . $programada->HORA_FINAL . '  La persona encargada de realizar la inspección será ' . $tecnico_sin_numero . '. 👷‍♂️' .
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
                ]);*/
                $programada->mensaje = 1;
                $programada->save();
            }
        } catch (QueryException $e) {
            Log::error($e);
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
            'fechaInicio' => 'required|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        try {
            $fecha_inicio = $request->fechaInicio;
            $fecha_fin = $request->fechaFin;
            if ($fecha_fin === null) {

                $columnasTabla = Schema::getColumnListing('tbl_programacion_contratos');
                $columnasAExcluir = ['updated_at', 'created_at'];
                $columnasAIncluir = array_diff($columnasTabla, $columnasAExcluir);

                $busqueda = DB::table('tbl_programacion_contratos AS pc') // Alias para tbl_programacion_contratos
                    ->join('tbl_programacion_base AS pb', 'pc.CONTRATO', '=', 'pb.CONTRATO')
                    ->join('tbl_programacion_usuarios AS pu', 'pc.id_programacion', '=', 'pu.id')
                    ->where('pc.FECHA_AGENDAMIENTO', '=', $fecha_inicio)
                    ->where('pc.EJECUTADA', '=', 0)
                    ->where('pu.finished', 1)
                    ->where('pb.ESTADO_RECEPCION', '=', 0)
                    ->select(
                        'pc.id',
                        'pc.CONTRATO',
                        'pc.TIPO_TRABAJO',
                        'pc.FECHA',
                        'pc.CELULAR',
                        'pc.NOMBRE_USUARIO',
                        'pc.ORDEN_TRABAJO',
                        'pc.DIRECCION',
                        'pc.BARRIO',
                        'pc.CIUDAD',
                        'pc.ACTIVA',
                        'pc.SUSPENDIDO',
                        'pc.CATEGORIA',
                        'pc.FECHA_AGENDAMIENTO',
                        'pc.OBSERVACIONES',
                        'pc.PORQUE_PROGRAMO',
                        'pc.TECNICO',
                        'pc.HORA_INICIO',
                        'pc.HORA_FINAL',
                    );

                $plantilla = DB::table('tbl_programacion_contratos')
                    ->where('FECHA_AGENDAMIENTO', '=', $fecha_inicio)
                    ->where('plantilla', 1)
                    ->select(
                        'id',
                        'CONTRATO',
                        'TIPO_TRABAJO',
                        'FECHA',
                        'CELULAR',
                        'NOMBRE_USUARIO',
                        'ORDEN_TRABAJO',
                        'DIRECCION',
                        'BARRIO',
                        'CIUDAD',
                        'ACTIVA',
                        'SUSPENDIDO',
                        'CATEGORIA',
                        'FECHA_AGENDAMIENTO',
                        'OBSERVACIONES',
                        'PORQUE_PROGRAMO',
                        'TECNICO',
                        'HORA_INICIO',
                        'HORA_FINAL',
                    );
            } else {
                $columnasTabla = Schema::getColumnListing('tbl_programacion_contratos');
                $columnasAExcluir = ['updated_at', 'created_at'];
                $columnasAIncluir = array_diff($columnasTabla, $columnasAExcluir);

                $busqueda = DB::table('tbl_programacion_contratos AS pc')
                    ->join('tbl_programacion_base AS pb', 'pc.CONTRATO', '=', 'pb.CONTRATO')
                    ->where('pc.EJECUTADA', '=', 0)
                    ->join('tbl_programacion_usuarios AS pu', 'pc.id_programacion', '=', 'pu.id')
                    ->where(function ($query) use ($fecha_inicio, $fecha_fin) {  // Agrupamos las condiciones
                        $query->whereBetween('FECHA_AGENDAMIENTO', [$fecha_inicio, $fecha_fin])
                            ->where(function ($subquery) {
                                $subquery->where('pb.ESTADO_RECEPCION', '=', 0)
                                    ->orWhereNull('pb.ESTADO_RECEPCION');
                            });
                    })
                    ->where('pu.finished', 1)
                    ->select(
                        'pc.id',
                        'pc.CONTRATO',
                        'pc.TIPO_TRABAJO',
                        'pc.FECHA',
                        'pc.CELULAR',
                        'pc.NOMBRE_USUARIO',
                        'pc.ORDEN_TRABAJO',
                        'pc.DIRECCION',
                        'pc.BARRIO',
                        'pc.CIUDAD',
                        'pc.ACTIVA',
                        'pc.SUSPENDIDO',
                        'pc.CATEGORIA',
                        'pc.FECHA_AGENDAMIENTO',
                        'pc.OBSERVACIONES',
                        'pc.PORQUE_PROGRAMO',
                        'pc.TECNICO',
                        'pc.HORA_INICIO',
                        'pc.HORA_FINAL',
                    );

                $plantilla = DB::table('tbl_programacion_contratos')
                    ->where('FECHA_AGENDAMIENTO', '>=', $fecha_inicio)
                    ->where('FECHA_AGENDAMIENTO', '<=', $fecha_fin)
                    ->where('plantilla', 1)
                    ->select(
                        'id',
                        'CONTRATO',
                        'TIPO_TRABAJO',
                        'FECHA',
                        'CELULAR',
                        'NOMBRE_USUARIO',
                        'ORDEN_TRABAJO',
                        'DIRECCION',
                        'BARRIO',
                        'CIUDAD',
                        'ACTIVA',
                        'SUSPENDIDO',
                        'CATEGORIA',
                        'FECHA_AGENDAMIENTO',
                        'OBSERVACIONES',
                        'PORQUE_PROGRAMO',
                        'TECNICO',
                        'HORA_INICIO',
                        'HORA_FINAL',
                    );
            }

            $plantilla = $plantilla->get();
            $busqueda = $busqueda->get();
            $uniqueData = [];
            $uniqueKeys = [];
            
            foreach ($busqueda as $item) {
                //unificar en un solo array
                $key = $item->ORDEN_TRABAJO . $item->FECHA_AGENDAMIENTO . $item->PORQUE_PROGRAMO;

                if (!in_array($key, $uniqueKeys)) {
                    $uniqueData[] = $item;
                    $uniqueKeys[] = $key;
                }
            }

            $busqueda = $uniqueData;

            foreach ($plantilla as $registro) {
                $busqueda[] = $registro; // Agregamos cada elemento de $plantilla al array $busqueda
            }

            return response()->json([
                'data' => $busqueda,
                'columnas' => $columnasAIncluir
            ]);
        } catch (Exception $e) {
            log::error($e);
            return response()->json(['error' => $e], 422);
        }
    }

    public function exportar(Request $request)
    {

        $data = $request->data;

        // Ignoramos el token ya que no es relevante para el CSV
        $rows = [];

        foreach ($data as $item) {
            if ($item[6] == "N/A" || $item[6] == null) {
                continue;
            }
            //sacar id del tecnico
            preg_match('/^(\d+)\./', $item[16], $matches);
            $numero = $matches[1];

            //sacar cedula del tecnico para la plantilla
            $cc_operario = tbl_insp_cali::select('cedula')
                ->where('id', $numero)
                ->first();

            //tipo de obra para GDW
            switch ($item[2]) {
                case '10444':
                    $tipo_trabajo = 37166;
                    break;
                case '12161':
                    $tipo_trabajo = 35699;
                    break;
                case '12162':
                    $tipo_trabajo = 35698;
                    break;
                case '12163':
                    $tipo_trabajo = 35701;
                    break;
                case '12164':
                    $tipo_trabajo = 35700;
                    break;
                case '12166':
                    $tipo_trabajo = 37179;
                    break;
                default:
                    $tipo_trabajo = "TIPO TAREA NO EXISTE";
                    break;
            }

            $fecha_original = $item[13];
            $hora_inicio = $item[17];
            $hora_final = $item[18];


            // Combinar fecha y hora en un formato que PHP pueda entender
            $fecha_hora_combinada_inicio = $fecha_original . ' ' . $hora_inicio;
            $fecha_hora_combinada_final = $fecha_original . ' ' . $hora_final;


            // Crear un objeto DateTime a partir de la fecha y hora combinada
            $objeto_fecha_inicio = DateTime::createFromFormat('Y-m-d h:i:s A', $fecha_hora_combinada_inicio);
            $objeto_fecha_final = DateTime::createFromFormat('Y-m-d h:i:s A', $fecha_hora_combinada_final);


            // Formatear la fecha en el formato deseado "d/m/Y h:i:s a"
            $fecha_formateada_inicio = $objeto_fecha_inicio->format('d/m/Y h:i:s a');
            $fecha_formateada_final = $objeto_fecha_final->format('d/m/Y h:i:s a');


            // Asegurarse de que la configuración regional esté en español para "a. m." y "p. m."
            setlocale(LC_TIME, 'es_ES.UTF-8');

            $rows[] = [
                ":" . $item[1],
                $item[7],
                $fecha_formateada_inicio,
                $fecha_formateada_final,
                'INSP-VALLE',
                $cc_operario->cedula,
                $tipo_trabajo,
                '1680',
                $item[14],
                '',
                '',
                ''
            ];
        }

        // Crear una nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Agregar los encabezados (opcional, pero recomendado)
        $headers = [
            'Nro contrato',
            'Direccion',
            'fecha Visita',
            'fecha Fin programado',
            'Grupo',
            'Nro Operario',
            'Id Tipo de Tarea',
            'Id Prioridad',
            'Detalle',
            'Nro de tarea interno',
            'Codigo del bien (opcional)'
        ];
        $sheet->fromArray($headers, NULL, 'A1');

        // Agregar los datos a la hoja
        $sheet->fromArray($rows, NULL, 'A2');

        // Crear el writer CSV
        $writer = new Csv($spreadsheet);

        // Establecer la configuración regional para el separador decimal (opcional)
        $writer->setDelimiter(';'); // Usar punto y coma como separador
        $writer->setEnclosure('');  // No usar ningún enclosure

        $writer->save(storage_path('app/uploads/') . 'archivo' . ".csv");
        // Generar la URL de descarga

        // Puedes retornar la URL o usarla como necesites
        return response()->json(['url' => '../storage/app/uploads/archivo.csv']);
    }

    public function masivos(Request $request)
    {

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

        $indicador = $this->validacionMasivas($worksheet);


        if (!$indicador) {
            return response()->json(['errors' => 'El archivo no cumple los criterios requeridos'], 422);
        }

        $datos = $this->Extdatos($worksheet);
        //$indicador = $this->notificacion($datos);


        if ($datos !== false) {
            session()->flash('success', 'Archivo subido exitosamente');
            return response()->json(['message' => 'Archivo subido exitosamente']);
        } else {
            return response()->json(['errors' => 'Error al subir el archivo'], 422);
        }
    }

    private function validacionMasivas($worksheet)
    {
        $indicador = true;
        foreach (['F', 'D', 'E', 'K', 'J', 'S', 'T', 'H', 'G', 'I', 'C', 'N', 'P', 'B'] as $columna) {
            switch ($columna) {
                case 'F':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Codigo de Instalacion") ? true : false;
                    break;
                case 'D':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Tipo de trabajo") ? true : false;
                    break;
                case 'E':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Fecha de ejecucion") ? true : false;
                    break;
                case 'K':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Telefono de Contacto1") ? true : false;
                    break;
                case 'J':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Nombre Usuario") ? true : false;
                    break;
                case 'S':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "ORDE MASIVA") ? true : false;
                    break;
                case 'T':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Orden externa") ? true : false;
                    break;
                case 'H':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Direccion") ? true : false;
                    break;
                case 'G':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Sector Operativo") ? true : false;
                    break;
                case 'I':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Municipio") ? true : false;
                    break;
                case 'C':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Categoria") ? true : false;
                    break;
                case 'N':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Fecha de Agendamiento") ? true : false;
                    break;
                case 'P':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Observacion de Agendamiento") ? true : false;
                    break;
                case 'B':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "Inspector") ? true : false;
                    break;
            }
        }
        return $indicador;
    }

    private function Extdatos($worksheet): bool
    {
        $tabla = new tbl_programacion_usuario;
        $tabla->nombre = "Programación tecnicos " . Carbon::now()->format('Y-m-d');
        $tabla->id_usuario = Auth::id();
        $tabla->finished = 1;
        $tabla->mensaje = 1;
        $tabla->save();

        try {
            foreach ($worksheet->getRowIterator() as $row) {
                if ($row->getRowIndex() === 1) {
                    continue; // Saltar la primera fila (encabezados)
                }
                $valor_filtro = $worksheet->getCell('N' . $row->getRowIndex())->getValue();
                $tipo_trabajo = $worksheet->getCell('D' . $row->getRowIndex())->getValue();
                $orden_masiva = $worksheet->getCell('S' . $row->getRowIndex())->getValue();
                if ($valor_filtro === "" || $valor_filtro === null || $tipo_trabajo === null || $orden_masiva === null) {
                    continue;
                }
                $programada = new tbl_programacion_contrato;

                $programada->ACTIVA = "Si";
                $programada->SUSPENDIDO = "No";
                $programada->PORQUE_PROGRAMO = "TECNICO MOVILIDAD";
                $programada->id_programacion = $tabla->id;
                $programada->mensaje = 1;

                if ($worksheet->getCell('O' . $row->getRowIndex())->getValue() === "MAÑANA") {
                    $programada->HORA_INICIO = "06:59:00 a.m.";
                    $programada->HORA_FINAL = "11:59:00 a.m.";
                }
                if ($worksheet->getCell('O' . $row->getRowIndex())->getValue() === " TARDE") {
                    $programada->HORA_INICIO = "11:59:00 a.m.";
                    $programada->HORA_FINAL = "04:59:00 p.m.";
                }
                if ($worksheet->getCell('O' . $row->getRowIndex())->getValue() === " TRANSCURSO DEL DIA") {
                    $programada->HORA_INICIO = "06:59:00 a.m.";
                    $programada->HORA_FINAL = "04:59:00 p.m.";
                }

                foreach (['F', 'D', 'E', 'K', 'J', 'S', 'H', 'G', 'I', 'C', 'N', 'P', 'B'] as $columna) {

                    $valorCelda = $worksheet->getCell($columna . $row->getRowIndex())->getValue();

                    switch ($columna) {
                        case 'F':
                            $contrato = str_replace(":", "", $valorCelda);
                            $programada->CONTRATO = $contrato;
                            break;
                        case 'D':
                            $programada->TIPO_TRABAJO = $valorCelda;
                            break;
                        case 'E':
                            $excelTimestamp = is_null($valorCelda) ? 0 : $valorCelda;
                            $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelTimestamp);
                            $programada->FECHA = $fecha->format('Y-m-d');
                            break;
                        case 'K':
                            $programada->CELULAR = $valorCelda;
                            break;
                        case 'J':
                            $programada->NOMBRE_USUARIO = $valorCelda;
                            break;
                        case 'S':
                            $valor_orden = $worksheet->getCell('T' . $row->getRowIndex())->getValue();
                            if ($valor_orden <> "" || $valor_orden <> null) {
                                $programada->ORDEN_TRABAJO = $worksheet->getCell('T' . $row->getRowIndex())->getValue();
                            } else {
                                $programada->ORDEN_TRABAJO = $valorCelda;
                            }
                            break;
                        case 'H':
                            $programada->DIRECCION = $valorCelda;
                            break;
                        case 'G':
                            $programada->BARRIO = $valorCelda;
                            break;
                        case 'I':
                            $programada->CIUDAD = $valorCelda;
                            break;
                        case 'C':
                            $programada->CATEGORIA = $valorCelda;
                            break;
                        case 'N':
                            $excelTimestamp = $valorCelda; // Supongamos que $valorCelda es "28/08/24"

                            // Elimina espacios en blanco y analiza la fecha con el formato específico
                            $dateTime = DateTime::createFromFormat('d/m/y', trim($excelTimestamp));

                            $excelTimestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateTime);
                            $fechaAsignacion = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelTimestamp);

                            $programada->FECHA_AGENDAMIENTO = $fechaAsignacion->format('Y-m-d');
                            break;
                        case 'P':
                            $jornada = $worksheet->getCell('O' . $row->getRowIndex())->getValue();
                            $programada->OBSERVACIONES = "JORNADA: " . $jornada . " OBSERVACIONES: " . $valorCelda;
                            break;
                        case 'B':
                            $resultados = tbl_insp_cali::whereRaw("CONCAT(apellidos, ' ', nombres) = ?", [$valorCelda])
                                ->first();
                            $programada->TECNICO = $resultados->id . ". " . $valorCelda;
                            break;
                    }
                }

                $cierres = [
                    'CERTIFICADA',
                    'CERTIFICADA CON NOVEDADES',
                    'INSPECCIONADA CON DEFECTO CRITICO VALLE',
                    'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
                ];
        
                $tipos_trabajo_rp = array("10444", "12161");
                $tipos_trabajo_sa = array("12163", "12164");
        
                if (in_array( $programada->TIPO_TRABAJO, $tipos_trabajo_rp)) {
                    $tipo_trabajo = ["RP 10444", "RP 12161"];
                } elseif (in_array( $programada->TIPO_TRABAJO, $tipos_trabajo_sa)) {
                    $tipo_trabajo = ["SA " .  $programada->TIPO_TRABAJO];
                } elseif ( $programada->TIPO_TRABAJO == "12162") {
                    $tipo_trabajo = ["RN " .  $programada->TIPO_TRABAJO];
                }
                $contrato = ':' . $programada->CONTRATO;

                $movilidad = Movilidad::select('NombreOperario', 'FechaRealInicio')
                    ->where('NroSitio',  $contrato)
                    ->whereIn('TipoTarea', $tipo_trabajo)
                    ->where('Grupo', 'INSP-VALLE')
                    ->whereIn('Cierre1', $cierres)
                    ->first();
        
                if ($movilidad) {
                    continue;
                }

                $exist = tbl_programacion_contrato::where('ORDEN_TRABAJO', $programada->ORDEN_TRABAJO)
                    ->where('CONTRATO', $programada->CONTRATO)
                    ->where('FECHA_AGENDAMIENTO',  $programada->FECHA_AGENDAMIENTO)
                    ->first();

                if ($exist) {
                    $exist->delete();
                }
                $programada->save();
            }
            return true;
        } catch (Exception $e) {
            //$tabla->delete();
            Log::error("Error al insertar datos: " . $e->getMessage()); // Registrar el error para depuración
            return false;
        }
    }

    public function programacionGDO(Request $request)
    {

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

        $indicador = $this->validacionGDO($worksheet);

        if (!$indicador) {
            return response()->json(['errors' => 'El archivo no cumple los criterios requeridos'], 422);
        }

        $datos = $this->ExtdatosGDO($worksheet);
        //$indicador = $this->notificacion($datos);


        if ($datos !== false) {
            session()->flash('success', 'Archivo subido exitosamente');
            return response()->json(['message' => 'Archivo subido exitosamente']);
        } else {
            return response()->json(['errors' => 'Error al subir el archivo'], 422);
        }
    }

    private function validacionGDO($worksheet)
    {
        $indicador = true;
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'] as $columna) {
            switch ($columna) {
                case 'A':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "CONTRATO") ? true : false;
                    break;
                case 'B':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "TIPO_TRABAJO") ? true : false;
                    break;
                case 'C':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "CELULAR") ? true : false;
                    break;
                case 'D':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "NOMBRE_USUARIO") ? true : false;
                    break;
                case 'E':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "ORDEN_TRABAJO_EXTERNA") ? true : false;
                    break;
                case 'F':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "DIRECCION") ? true : false;
                    break;
                case 'G':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "BARRIO") ? true : false;
                    break;
                case 'H':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "CIUDAD") ? true : false;
                    break;
                case 'I':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "ESTADO") ? true : false;
                    break;
                case 'J':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "CATEGORIA") ? true : false;
                    break;
                case 'K':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "FECHA_AGENDAMIENTO") ? true : false;
                    break;
                case 'L':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "OBSERVACIONES") ? true : false;
                    break;
                case 'M':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "TECNICO") ? true : false;
            }
        }
        return $indicador;
    }
    private function ExtdatosGDO($worksheet): bool
    {
        $tabla = new tbl_programacion_usuario;
        $tabla->nombre = "Programación GDO " . Carbon::now()->format('Y-m-d');
        $tabla->id_usuario = Auth::id();
        $tabla->finished = 1;
        $tabla->mensaje = 1;
        $tabla->save();

        try {
            foreach ($worksheet->getRowIterator() as $row) {
                if ($row->getRowIndex() === 1) {
                    continue; // Saltar la primera fila (encabezados)
                }

                $programada = new tbl_programacion_contrato;

                $estado = $worksheet->getCell('I' . $row->getRowIndex())->getValue();
                if ($estado === "Activo") {
                    $programada->ACTIVA = "Si";
                    $programada->SUSPENDIDO = "No";
                } else {
                    $programada->ACTIVA = "No";
                    $programada->SUSPENDIDO = "Si";
                }
                $programada->FECHA = date('Y-m-d');
                $programada->PORQUE_PROGRAMO = "PROGRAMACION GDO";
                $programada->id_programacion = $tabla->id;
                $programada->mensaje = 1;

                $jornada = $worksheet->getCell('L' . $row->getRowIndex())->getValue();
                $fecha_visita = $worksheet->getCell('K' . $row->getRowIndex())->getValue();

                // Expresión regular para encontrar "JORNADA VISITA" seguido de dos letras
                $patron = '/JORNADA VISITA (\w{2})/';

                // Busca la coincidencia en la cadena $jornada
                if (preg_match($patron, $jornada, $coincidencias)) {
                    // Si hay coincidencia, la jornada se encuentra en $coincidencias[1]
                    $jornadaVisita = $coincidencias[1];
                    if ($jornadaVisita == "AM") {
                        $programada->HORA_INICIO = "06:59:00 a.m.";
                        $programada->HORA_FINAL = "11:59:00 a.m.";
                    }
                    if ($jornadaVisita == "PM") {
                        $programada->HORA_INICIO = "11:59:00 a.m.";
                        $programada->HORA_FINAL = "04:59:00 p.m.";
                    }
                } else {
                    return false;
                }
                $existe = 0;
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M'] as $columna) {

                    $valorCelda = $worksheet->getCell($columna . $row->getRowIndex())->getValue();

                    switch ($columna) {
                        case 'A':
                            $exist = tbl_programacion_contrato::where('CONTRATO', $valorCelda)->exists();
                            if ($exist) {
                                $existe = $existe + 1;
                            }
                            $programada->CONTRATO = $valorCelda;
                            break;
                        case 'B':
                            $programada->TIPO_TRABAJO = $valorCelda;
                            break;
                        case 'C':
                            $programada->CELULAR = $valorCelda;;
                            break;
                        case 'D':
                            $programada->NOMBRE_USUARIO = $valorCelda;
                            break;
                        case 'E':
                            $exist = tbl_programacion_contrato::where('ORDEN_TRABAJO', $valorCelda)->exists();
                            if ($exist) {
                                $existe = $existe + 1;
                            }
                            $programada->ORDEN_TRABAJO = $valorCelda;
                            break;
                        case 'F':
                            $programada->DIRECCION = $valorCelda;
                            break;
                        case 'G':
                            $programada->BARRIO = $valorCelda;
                            break;
                        case 'H':
                            $programada->CIUDAD = $valorCelda;
                            break;
                        case 'J':
                            $programada->CATEGORIA = $valorCelda;
                            break;
                        case 'K':
                            $patron = '/FECHA DE VISITA (\d{4}-\d{2}-\d{2})/';
                            preg_match($patron, $fecha_visita, $coincidencias);

                            $programada->FECHA_AGENDAMIENTO = $coincidencias[1];
                            break;
                        case 'L':
                            $programada->OBSERVACIONES = $valorCelda;
                            break;
                        case 'M':
                            if ($valorCelda == null || $valorCelda == "") {
                                break;
                            }
                            $inspector = tbl_insp_cali::where('id', $valorCelda)->first();
                            // Modificar la propiedad ID_TECNICO con el resultado de la consulta
                            $programada->TECNICO = $valorCelda . '. ' . $inspector->apellidos . ' ' . $inspector->nombres;
                            break;
                    }
                }
                /* $exist = tbl_programacion_contrato::where('CONTRATO', $programada->CONTRATO)
                    ->where('ORDEN_TRABAJO', $programada->ORDEN_TRABAJO)
                    ->where('TIPO_TRABAJO', $programada->TIPO_TRABAJO)
                    ->exists();
                if ($exist) {
                    continue;
                } */
                if ($existe >= 2) {
                    continue;
                }

                $cierres = [
                    'CERTIFICADA',
                    'CERTIFICADA CON NOVEDADES',
                    'INSPECCIONADA CON DEFECTO CRITICO VALLE',
                    'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
                ];
        
                $tipos_trabajo_rp = array("10444", "12161");
                $tipos_trabajo_sa = array("12163", "12164");
        
                if (in_array( $programada->TIPO_TRABAJO, $tipos_trabajo_rp)) {
                    $tipo_trabajo = ["RP 10444", "RP 12161"];
                } elseif (in_array( $programada->TIPO_TRABAJO, $tipos_trabajo_sa)) {
                    $tipo_trabajo = ["SA " .  $programada->TIPO_TRABAJO];
                } elseif ( $programada->TIPO_TRABAJO == "12162") {
                    $tipo_trabajo = ["RN " .  $programada->TIPO_TRABAJO];
                }
                $contrato = ':' . $programada->CONTRATO;

                $movilidad = Movilidad::select('NombreOperario', 'FechaRealInicio')
                    ->where('NroSitio',  $contrato)
                    ->whereIn('TipoTarea', $tipo_trabajo)
                    ->where('Grupo', 'INSP-VALLE')
                    ->whereIn('Cierre1', $cierres)
                    ->first();
        
                if ($movilidad) {
                    continue;
                }


                $programada->save();
            }
            return true;
        } catch (Exception $e) {
            //$tabla->delete();
            Log::error("Error al insertar datos: " . $e->getMessage()); // Registrar el error para depuración
            return false;
        }
    }



    private function notificacion($datos)
    {
        foreach ($datos as $dato) {

            $observacion = $dato['Observación externa'];

            // Utilizamos una expresión regular para buscar números de celular
            preg_match_all('/\b3\d{9}\b/', $observacion, $coincidencias);

            // Si se encontraron coincidencias, las mostramos o las almacenamos
            if (!empty($coincidencias[0])) {
                foreach ($coincidencias[0] as $numeroCelular) {

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

                    $bodyData = [
                        'typing_time' => 0,
                        'to' => '57' . $numeroCelular,
                        'body' => $saludo . ',

E&C Ingeniería de Gases de Occidente solicita programar la inspección de revisión periódica de la red de gas ubicada en el predio con los siguientes datos:

* Contrato: ' . $dato['Contrato'] . '
* Dirección: ' . $dato['Dirección'] . ' | ' . $dato['Localidad'] . '
* A nombre de: ' . $dato['Nombre'] . '
* Medidor: ' . $dato['Medidor'] . '

Agradecemos su colaboración para coordinar esta inspección a la brevedad posible.'
                    ];

                    $client = new Client();

                    $response = $client->request('POST', 'https://gate.whapi.cloud/contacts', [
                        'json' => [ // Usamos 'json' en lugar de 'body' para enviar datos JSON
                            'blocking' => 'no_wait',
                            'force_check' => false,
                            'contacts' => ['+57' . $numeroCelular] // Lista de números a verificar
                        ],
                        'headers' => [
                            'accept' => 'application/json',
                            'authorization' => 'Bearer bGBktWXeKxgX1syNGKtT8al4rfZHRemt',
                            'content-type' => 'application/json',
                        ],
                    ]);

                    $data = json_decode($response->getBody(), true);

                    if ($data['contacts'][0]['status'] === 'invalid') {
                        continue;
                    }

                    $response = $client->request('POST', 'https://gate.whapi.cloud/messages/text', [
                        'json' => $bodyData,
                        'headers' => [
                            'accept' => 'application/json',
                            'authorization' => 'Bearer bGBktWXeKxgX1syNGKtT8al4rfZHRemt',
                            'content-type' => 'application/json',
                        ],
                    ]);
                    if ($response->getStatusCode() === 200) {
                        break;
                    } else {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function buscarPorContrato(Request $request)
    {

        $contrato = $request->input('contrato');
        $array = array();

        try {
            $programadas = tbl_programacion_usuario::whereIn(
                'id',
                tbl_programacion_contrato::select('id_programacion')
                    ->where('CONTRATO', 'LIKE', '%' . $contrato . '%')
            )->get();

            foreach ($programadas as $programada) {
                $usuario = User::find($programada->id_usuario);

                $array[] = [
                    'id' => $programada->id,
                    'nombre' => $programada->nombre,
                    'usuario' => $usuario->name,
                ];
            }
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['error' => $e], 422);
        }
        return response()->json($array);
    }

    public function PlantillaStore(Request $request)
    {

        try {
            $programacion = new tbl_programacion_contrato();
            $programacion->CONTRATO = $request->data['CONTRATO'];
            $programacion->TIPO_TRABAJO = $request->data['TIPO_TRABAJO'];
            $programacion->FECHA = $request->data['FECHA'];
            $programacion->CELULAR = $request->data['CELULAR'];
            $programacion->NOMBRE_USUARIO = $request->data['NOMBRE_USUARIO'];
            $programacion->ORDEN_TRABAJO = $request->data['ORDEN_TRABAJO'];
            $programacion->DIRECCION = $request->data['DIRECCION'];
            $programacion->BARRIO = $request->data['BARRIO'];
            $programacion->CIUDAD = $request->data['CIUDAD'];
            $programacion->ACTIVA = $request->data['ACTIVA'];
            $programacion->SUSPENDIDO = $request->data['SUSPENDIDO'];
            $programacion->CATEGORIA = $request->data['CATEGORIA'];
            $programacion->FECHA_AGENDAMIENTO = $request->data['FECHA_AGENDAMIENTO'];
            $programacion->OBSERVACIONES = $request->data['OBSERVACIONES'];
            $programacion->PORQUE_PROGRAMO = $request->data['PORQUE_PROGRAMO'];
            $programacion->TECNICO = $request->data['TECNICO'];
            $programacion->HORA_INICIO = $request->data['HORA_INICIO'];
            $programacion->HORA_FINAL = $request->data['HORA_FINAL'];
            $programacion->id_programacion = $request->tabla;
            $programacion->plantilla = 1;
            $programacion->save();
        } catch (QueryException $e) {

            log::error($e);
            return response()->json(['error' => $e]);
        }
        return response()->json(['message' => 'Registro guardado correctamente', 'id' => $programacion->id], 200);
    }
}
