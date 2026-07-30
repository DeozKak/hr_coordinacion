<?php

namespace App\Http\Controllers;


use App\Services\ProgramacionService;
use App\Jobs\CorreoProgramacion;
use App\Models\Programacion\tbl_programacion_base;
use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Programacion\tbl_programacion_usuario;
use App\Models\tbl_insp_cali;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\ExtraerFechas;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessExcelFileMacros;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Jobs\ProcessCallCenterGdo;
use App\Services\Programacion\ReAsignacion;

class ProgramacionController extends Controller
{

    private $programacionService;

    public function __construct(ProgramacionService $programacionService)
    {
        $this->programacionService = $programacionService;
    }

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
            try {
                DB::beginTransaction();
                $fechaActual = Carbon::now();
                $soloFecha = $fechaActual->format('Y-m-d');
                $programacion = new tbl_programacion_usuario;
                $programacion->nombre = "Programación " . $soloFecha;
                $programacion->id_usuario = Auth::id();
                $programacion->save();

                $tecnicos = tbl_insp_cali::select('id', 'apellidos', 'nombres')
                    ->where('state', 1)
                    ->orderBy('apellidos') // Ordenar por apellidos ascendente
                    ->get();

                $user = Auth::user();
                DB::commit();
                return view('programacion.create', compact('tecnicos', 'user', 'programacion'));
            } catch (\Exception $e) {
                Log::error($e);
                DB::rollback();
                session()->flash('error', 'Ocurrió un error al crear tabla ' . $e->getMessage());
                return redirect()->route('programacion.index');
            }
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
                try {
                    DB::beginTransaction();
                    $programacion->finished = 0;
                    $programacion->save();
                    DB::commit();
                } catch (\Exception $e) {
                    log::error($e);
                    DB::rollback();
                    session()->flash('error', 'Ocurrió un error al cargar tabla ' . $e->getMessage());
                    return redirect()->route('programacion.index');
                }
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

    public function base(Request $request): \Illuminate\Http\JsonResponse
    {

        $valorCheckBox = $request->input('check_estado5');
        $request->validate([
            'archivo' => 'required|file|mimes:xls,xlsx',
        ], [
            'archivo.required' => 'El campo archivo es obligatorio.',
            'archivo.file' => 'El valor debe ser un archivo.',
            'archivo.mimes' => 'El archivo debe ser de tipo XLS o XLSX.',
        ]);

        try {
            if ($valorCheckBox == 1) {
                $file = IOFactory::load($request->file('archivo'));
                $date = Datetime::createFromFormat('Y/m/d', Carbon::now()->format('Y/m/d'));
                $worksheet = $file->getActiveSheet();
                $indicador = $this->validacionGDO($worksheet);
                if ($indicador == true) {
                    $indicador = $this->insertBase($worksheet);
                    if($indicador == true){
                        return response()->json(['message' => 'Se ha cargado correctamente la base de datos'], 200);
                    }else{
                        return response()->json(['errors' => 'Error al cargar la base de datos'], 422);
                    }
                } else {
                    return response()->json(['errors' => 'El archivo no cumple con el formato requerido'], 422);
                }
            }
            // 1. Guardar el archivo en el storage de Laravel para que el Job pueda acceder a él.
            // Esto lo guardará en la carpeta 'storage/app/excel-imports'
            $path = $request->file('archivo')->store('excel-imports');

            // 2. Realizar la validación RÁPIDA de los encabezados
            $reader = ReaderEntityFactory::createXLSXReader();
            // Usamos storage_path() para obtener la ruta absoluta del archivo guardado
            $reader->open(storage_path('app/' . $path));

            $isValid = false;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    // Validamos la primera fila y salimos del bucle inmediatamente.
                    $isValid = $this->validacionConArray($row->toArray());
                    break; // <-- Salimos después de leer la primera fila
                }
                break; // <-- Salimos después de leer la primera hoja
            }
            $reader->close();

            // 3. Si la validación falla, borramos el archivo y devolvemos un error.
            if (!$isValid) {
                Storage::delete($path); // Limpiamos el archivo subido
                return response()->json(['errors' => 'La estructura del archivo o los encabezados no son correctos.'], 422);
            }
            $originalName = $request->file('archivo')->getClientOriginalName(); // Obtener el nombre original

            // 4. Si todo está bien, despachamos el Job pasándole la ruta del archivo.
            ProcessExcelFileMacros::dispatch($path, Auth::user(), $originalName);

            // 5. Devolvemos una respuesta inmediata al usuario.
            // El código de estado 202 "Accepted" es ideal para esto.
            return response()->json(['message' => 'El archivo ha sido aceptado y se está procesando en segundo plano.'], 202);

        } catch (\Exception  $e) {
            Log::error("Error en la subida inicial del archivo: " . $e->getMessage());
            return response()->json(['errors' => 'No se pudo procesar la solicitud de subida.'], 500);
        }
    }

    public function validacionConArray(array $headerRow): bool
    {
        $expectedHeaders = [
            "Orden", "Contrato", "Producto", "Numero solicitud", "Tipo solicitud"
        ];

        // Comparamos solo las primeras columnas necesarias
        $headersToValidate = array_slice($headerRow, 0, count($expectedHeaders));

        // Eliminamos espacios adicionales de los headers para evitar errores
        $headersToValidate = array_map('trim', $headersToValidate);
        $expectedHeaders = array_map('trim', $expectedHeaders);

        return $headersToValidate === $expectedHeaders;

    }


    public function busqueda($contrato): ?\Illuminate\Http\JsonResponse
    {
        // Validación numérica
        if (!is_numeric($contrato)) {
            return null;
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

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {

        $data = $request->data;


        $executed = $this->programacionService->findExecuted($request->data[1],$request->data[2],$request->data[6]);

        if($executed) {
            if ($executed->TIPO_TRABAJO === 'SA 12164') {

            } else {
                $fecha_completa = $executed->FECHA;
                $partes = explode(' ', $fecha_completa);
                $fecha = $partes[0];
                /* if ($fecha <= $dosAnosAtras) {

                 } else {*/
                $inspector = tbl_insp_cali::where('cedula', $executed->CC_OPERARIO)->first();
                return response()->json([
                    'movilidad' => 'Contrato ya ejecutado',
                    'usuario' => $inspector->apellidos.' '.$inspector->nombres,
                    'agendamiento' => $fecha
                ]);
                /* }*/
            }
        }

        if(in_array($request->data[2],['10444','12161'])){
            // Validar si ya existe el contrato con los mismos datos.
            $exist = tbl_programacion_contrato::where('CONTRATO', $request->data[1])
                ->whereIn('TIPO_TRABAJO', ['10444','12161'])
                ->first();
        }else{
            $exist = tbl_programacion_contrato::where('CONTRATO', $request->data[1])
                ->where('ORDEN_TRABAJO', $request->data[6])
                ->first();
        }
        if ($exist) {
            return response()->json([
                'exist' => 'Ya existe una programación con estos datos',
                'id' => $exist->id,
                'usuario' => $exist->PORQUE_PROGRAMO,
                'agendamiento' => $exist->FECHA_AGENDAMIENTO
            ]);
        }

        try {
            DB::beginTransaction();
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
            $programacion->JORNADA = $request->data[17];
            $programacion->id_programacion = $request->tabla;
            $programacion->save();
            DB::commit();
        } catch (QueryException $e) {
            log::error($e);
            DB::rollback();
            return response()->json(['error' => 'Error al guardar en base de datos. ' . $e->getMessage()]);
        }

        return response()->json(['message' => 'Registro guardado correctamente', 'id' => $programacion->id]);
    }

    public function update($id, Request $request): \Illuminate\Http\JsonResponse
    {

        try {
            DB::beginTransaction();

            $programacion = tbl_programacion_contrato::find($id);
            $campo = $request->propiedad;
            if ($campo === 'FECHA_AGENDAMIENTO') {
                try {
                    $fecha = Carbon::createFromFormat('Y-m-d', $request->valor);
                    // Validación extra para rechazar fechas no exactas al formato (como '2024-1-9')
                    if ($fecha->format('Y-m-d') !== $request->valor) {
                        return response()->json(['error' => 'La fecha debe tener el formato correcto (Y-m-d).'], 422);
                    }
                } catch (\Exception $e) {
                    return response()->json(['error' => 'La fecha debe tener el formato correcto (Y-m-d).'], 422);
                }
            }

            if($campo === 'JORNADA'){
                $programacion->HORA_INICIO = "06:59:00 a.m.";
                $programacion->HORA_FINAL = "04:59:00 p.m.";
            }

            $programacion->$campo = $request->valor;
            $programacion->save();
            DB::commit();
        } catch (QueryException $e) {
            Log::error($e);
            DB::rollback();
            return response()->json(['error' => 'Error al actualizar registro. ' . $e->getMessage()],500);
        }
        return response()->json(['message' => 'Registro actualizado correctamente']);
    }

    public function destroy(Request $request): \Illuminate\Http\JsonResponse
    {

        try {
            DB::beginTransaction();
            $id = $request->data;
            $programacion = tbl_programacion_contrato::find($id);
            $programacion->delete();
            DB::commit();
        } catch (QueryException $e) {
            Log::error($e);
            DB::rollback();
            return response()->json(['error' => 'Error al eliminar registro. ' . $e]);
        }
        return response()->json(['message' => 'Registro eliminado correctamente']);
    }

    public function erase($id): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $programacion = tbl_programacion_usuario::find($id);
            $contratos = tbl_programacion_contrato::where('id_programacion', $id)->get();
            $contratos->each->delete();
            $programacion->delete();
            DB::commit();
        } catch (QueryException $e) {
            log::error($e);
            DB::rollback();
            return response()->json(['error' => 'Error al eliminar Programación. ' . $e]);
        }
        return response()->json(['message' => 'Programación eliminada correctamente']);
    }

    public function finish($id): \Illuminate\Http\JsonResponse
    {

        try {
            DB::beginTransaction();
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

                try {
                    $fecha_carbon = Carbon::createFromFormat('Y-m-d', $programada->FECHA_AGENDAMIENTO);
                    // Validación extra para rechazar fechas no exactas al formato (como '2024-1-9')
                    if ($fecha_carbon->format('Y-m-d') !== $programada->FECHA_AGENDAMIENTO) {
                        return response()->json(['error' => 'La fecha debe tener el formato correcto (Y-m-d).'], 422);
                    }
                } catch (\Exception $e) {
                    return response()->json(['error' => 'La fecha debe tener el formato correcto (Y-m-d).'], 422);
                }
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
            DB::commit();
        } catch (QueryException $e) {
            Log::error($e);
            DB::rollback();
            return response()->json(['error' => 'Error al finalizar Programación. ' . $e]);
        }
        session()->flash('success', 'Programación finalizada correctamente');
        return response()->json(['ok' => 'Programación finalizada correctamente']);
    }

    public function detalles()
    {
        $tecnicos = tbl_insp_cali::where('state','1')->get();
        return view('programacion.ver',compact('tecnicos'));
    }

    public function agendamiento(Request $request): \Illuminate\Http\JsonResponse
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
                $elemento = array_splice($columnasTabla, 19, 1);
                array_splice($columnasTabla, 17, 0, $elemento);
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
                        'pc.JORNADA',

                    );


                $plantilla = DB::table('tbl_programacion_contratos')
                    ->where('FECHA_AGENDAMIENTO', '=', $fecha_inicio)
                    ->where('EJECUTADA', '=', 0)
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
                        'JORNADA',
                    );
            } else {
                $columnasTabla = Schema::getColumnListing('tbl_programacion_contratos');
                $elemento = array_splice($columnasTabla, 19, 1);
                array_splice($columnasTabla, 17, 0, $elemento);
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
                        'pc.JORNADA',
                    );

                $plantilla = DB::table('tbl_programacion_contratos')
                    ->where('FECHA_AGENDAMIENTO', '>=', $fecha_inicio)
                    ->where('FECHA_AGENDAMIENTO', '<=', $fecha_fin)
                    ->where('EJECUTADA', '=', 0)
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
                        'JORNADA',
                    );
            }

            $plantilla = $plantilla->orderBy('TECNICO')->get();
            $busqueda = $busqueda->orderBy('TECNICO')->get();


            // 1. Concatenamos ambas colecciones (Plantilla queda de primera)
            $coleccionCombinada = $plantilla->concat($busqueda);

            // 2. Filtramos por 'id' único.
            // unique() conservará el primer elemento que encuentre, dando prioridad a $plantilla.
            // 3. values() resetea los índices (0, 1, 2, 3...) para que el JSON quede como un array limpio.
            $finalData = $coleccionCombinada->unique('id')->values();

            // dd($finalData); // Descomenta esto para verificar la limpieza antes de retornar

            return response()->json([
                'data' => $finalData,
                'columnas' => $columnasAIncluir
            ]);
        } catch (Exception $e) {
            log::error($e);
            return response()->json(['error' => 'Error al consultar agendamiento. ' . $e->getMessage()], 422);
        }
    }

    public function exportar(Request $request): \Illuminate\Http\JsonResponse
    {

        $data = $request->data;
        //dd($data);
        // Ignoramos el token ya que no es relevante para el CSV
        $rows = [];

        foreach ($data as $index => $item) {
            try {
                if ($item[6] == "N/A" || $item[6] == null) {
                    continue;
                }
                if ($item[16] == "" || $item[16] == null) {
                    return response()->json(['error' => 'Programación sin tecnico, revise la fila ' . $index + 1], 422);
                }
                //sacar id del tecnico
                preg_match('/^(\d+)\./', $item[16], $matches);
                $numero = $matches[1];

                //sacar cedula del tecnico para la plantilla
                $cc_operario = tbl_insp_cali::select('cedula')
                    ->where('id', $numero)
                    ->first();
                if ($cc_operario == null) {
                    return response()->json(['error' => 'No se encuentra id técnico, revise fila ' . $index + 1], 422);
                }
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
                //modificacion hora inicio para que salgan desde la mañana
                $hora_inicio = '06:59:00 a.m.';
                $hora_final = '05:59:00 p.m.';
                if ($hora_inicio == null || $hora_final == null || $fecha_original == null) {
                    return response()->json(['error' => 'Falta hora inicio o hora final, revise fila ' . $index + 1], 422);
                }

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
                    '1934',
                    'TEL: ' . $item[4] . ' Nombre Usuario: ' . $item[5] . ' ' . $item[14], //Detalle
                    '',
                    '',
                    ''
                ];
            } catch (\Exception $e) {
                // dd($data, $index);
                return response()->json(['error' => 'Error al exportar. ' . $e->getMessage()], 500);
            }
        }
        try {
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
            $nombreArchivo = 'Plantilla Programacion GDW ' . date('Y-m-d H-i-s') . '.csv';

            $writer->save(storage_path('app/uploads/') . $nombreArchivo);
            // Generar la URL de descarga
            $url = url()->temporarySignedRoute(
                'descargar.archivo', // Usa la nueva ruta genérica
                now()->addMinutes(10), // Expiración en 10 minutos
                ['file' => $nombreArchivo] // Archivo como parámetro
            );
            // Puedes retornar la URL o usarla como necesites
            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar archivo. ' . $e->getMessage()], 500);
        }
    }

    public function exportarSup(Request $request)
    {
        $data = $request->data; // array de registros
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        foreach ($data as $index => $item) {
            if ($item[16] == "" || $item[16] == null) {
                return response()->json(['error' => 'Programación sin tecnico, revise la fila ' . $index + 1], 422);
            }
        }

        try {
            // Organizar registros por supervisor
            $agrupadosPorSupervisor = $this->agruparPorSupervisor($data);

            // Crear carpeta temporal
            $carpetaTmp = storage_path('app/uploads/');
            if (!File::exists($carpetaTmp)) {
                File::makeDirectory($carpetaTmp, 0755, true);
            }
            $archivos = [];

            // PDFs por supervisor
            foreach ($agrupadosPorSupervisor as $supInfo) {
                $archivos[] = $this->generarPdfSupervisor($supInfo['supervisor'], $supInfo['registros'], $carpetaTmp, $fechaInicio, $fechaFin);
            }

            // Excel global
            $archivos[] = $this->generarExcelTotal($data, $carpetaTmp, $fechaInicio, $fechaFin);

            // Nombre ZIP
            $nombreZip = 'AGENDAMIENTO_'
                . ($fechaInicio ? str_replace('-', '_', $fechaInicio) : '')
                . ($fechaFin ? ('_' . str_replace('-', '_', $fechaFin)) : '')
                . '.zip';


            $zipPath = $this->empaquetarArchivosZip($archivos, $carpetaTmp . $nombreZip);

            // Borrar archivos temporales individuales (no el ZIP)
            foreach ($archivos as $archivo) {
                if (file_exists($archivo)) {
                    unlink($archivo);
                }
            }

            // Genera ruta firmada (10 minutos de validez)
            $url = url()->temporarySignedRoute(
                'descargar.archivo',
                now()->addMinutes(10),
                ['file' => $nombreZip]
            );
        } catch (\Exception $e) {
            log::error($e);
            return response()->json(['error' => 'Error al generar archivo. ' . $e->getMessage()], 500);
        }
        return response()->json(['url' => $url]);
    }

    private function generarPdfSupervisor($supervisor, $registros, $destino, $fechaInicio, $fechaFin): string
    {
        $html = view('reportes.supervisor_pdf', compact('supervisor', 'registros'))->render();
        $mpdf = new Mpdf(['orientation' => 'L']);

        $nombreLimpio = preg_replace('/[^A-Za-z0-9_\-]/', '_', $supervisor->nombre);
        if (empty($nombreLimpio)) { $nombreLimpio = "supervisor_" . $supervisor->id; }

        $fileName = 'reporte_' . $nombreLimpio . "_" . time() . '.pdf'; // Añadimos time() para evitar colisiones
        $filePath = $destino . $fileName;

        $mpdf->WriteHTML($html);
        $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

        return $filePath;
    }

    private function generarExcelTotal($data, $destino, $fechaInicio, $fechaFin): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Definir los encabezados
        $headers = [
            'Contrato', 'Tipo de trabajo', 'Fecha', 'Celular', 'Nombre de Usuario', 'Orden de trabajo', 'Direccion',
            'Barrio', 'Ciudad', 'Activa', 'Suspendida', 'Categoria', 'Fecha de agendamiento', 'Observaciones',
            'Quien programo', 'Tecnico', 'Jornada'
        ];
        $sheet->fromArray($headers, NULL, 'A1');

        // Estilos encabezado
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'] // Azul claro
            ]
        ];
        $cellRange = 'A1:R1'; // 18 columnas (R es la columna 18)
        $sheet->getStyle($cellRange)->applyFromArray($headerStyle);

        // Agregar los datos
        $rowNum = 2;
        foreach ($data as $fila) {
            $filaSinPrimero = array_slice($fila, 1); // Ignora el primer elemento
            $sheet->fromArray($filaSinPrimero, NULL, 'A' . $rowNum++);
        }

        // Aplicar borde tipo tabla a todo
        $totalRows = count($data) + 1; // +1 por el header
        $lastCol = 'R';
        $tableRange = "A1:$lastCol$totalRows";
        $tableStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ]
            ]
        ];
        $sheet->getStyle($tableRange)->applyFromArray($tableStyle);

        // Autoajustar el contenido de las columnas
        for ($col = 'A'; $col <= $lastCol; $col++) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filePath = $destino . 'Agendamiento_total ' . ($fechaInicio ? str_replace('-', '_', $fechaInicio) : '')
            . ($fechaFin ? ('_' . str_replace('-', '_', $fechaFin)) : '') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);
        return $filePath;
    }

    private function empaquetarArchivosZip($archivos, $zipPath)
    {
        $zip = new ZipArchive;

        // Intentar abrir/crear el archivo ZIP
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($archivos as $archivo) {
                // VERIFICACIÓN CRÍTICA: ¿El archivo realmente existe en el disco?
                if (file_exists($archivo) && is_readable($archivo)) {
                    $zip->addFile($archivo, basename($archivo));
                } else {
                    Log::error("El archivo no pudo ser agregado al ZIP porque no existe: " . $archivo);
                    // Opcional: lanzar una excepción si prefieres detener el proceso
                    // throw new Exception("Archivo faltante: " . basename($archivo));
                }
            }
            $zip->close();
        } else {
            throw new Exception("No se pudo crear el archivo ZIP en: " . $zipPath);
        }

        return $zipPath;
    }

    private function agruparPorSupervisor($data): array
    {
        // Ejemplo básico. Cambia según tu formato de datos.
        $resultado = [];
        foreach ($data as $item) {
            // 1. Extraer el ID de tbl_insp_cali del campo 16 (index 16)
            $supId = (int)strtok($item[16], '.');
            // 2. Obtener el registro de tbl_insp_cali
            $registroCali = tbl_insp_cali::find($supId);
            if ($registroCali && $registroCali->supervisor) {
                $userId = $registroCali->supervisor->id;
                if (!isset($resultado[$userId])) {
                    $resultado[$userId] = [
                        'supervisor' => (object)[
                            'id' => $userId,
                            'nombre' => $registroCali->supervisor->name,
                        ],
                        'registros' => []
                    ];
                }
                $resultado[$userId]['registros'][] = $item;
            }
        }
        return array_values($resultado);
    }

    public function masivos(Request $request): \Illuminate\Http\JsonResponse
    {

        $request->validate([
            'archivo' => 'required|file|mimes:xls,xlsx',
        ], [
            'archivo.required' => 'El campo archivo es obligatorio.',
            'archivo.file' => 'El valor debe ser un archivo.',
            'archivo.mimes' => 'El archivo debe ser de tipo XLS o XLSX.',
        ]);
        //dd("HOLA");
        $archivo = $request->file('archivo');
        $spreadsheet = IOFactory::load($archivo);
        $worksheet = $spreadsheet->getActiveSheet();

        $indicador = $this->validacionMasivas($worksheet);

        if (!$indicador) {
            return response()->json(['errors' => 'El archivo no cumple los criterios requeridos'], 422);
        }

        $datos = $this->Extdatos($worksheet);
        //$indicador = $this->notificacion($datos);
        if ($datos === true) {
            session()->flash('success', 'Archivo subido exitosamente');
            return response()->json(['message' => 'Archivo subido exitosamente']);
        } else {
            // en caso de error o no cumplir con requisitos $datos devuelve un string con el mensaje
            return response()->json(['errors' => $datos], 422);
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

    private function Extdatos($worksheet): true|string
    {
        try {
            DB::beginTransaction();
            $tabla = new tbl_programacion_usuario;
            $tabla->nombre = "Programación tecnicos " . Carbon::now()->format('Y-m-d');
            $tabla->id_usuario = Auth::id();
            $tabla->finished = 1;
            $tabla->mensaje = 1;
            $tabla->save();


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

                if (strpos($worksheet->getCell('O' . $row->getRowIndex())->getValue(), "MAÑANA") !== false) {
                   $programada->JORNADA = "mañana";
                } elseif (strpos($worksheet->getCell('O' . $row->getRowIndex())->getValue(), "TARDE") !== false) {
                    $programada->JORNADA = "tarde";
                } elseif (strpos($worksheet->getCell('O' . $row->getRowIndex())->getValue(), "TRANSCURSO DEL DIA") !== false) {
                    $programada->JORNADA = "todo el dia";
                } else {
                    $programada->JORNADA = "todo el dia";
                }

                $programada->HORA_INICIO = "06:59:00 a.m.";
                $programada->HORA_FINAL = "11:59:00 a.m.";
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
                            try {
                                $excelTimestamp = is_null($valorCelda) ? 0 : $valorCelda;
                                $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelTimestamp);
                                $programada->FECHA = $fecha->format('Y-m-d');
                            } catch (\Exception $e) {
                                log::error($e);
                                DB::rollBack();
                                return 'Error al convertir fecha. revise columna E Fila ' . $row->getRowIndex();
                            }
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
                            try {
                                $excelTimestamp = $valorCelda; // Supongamos que $valorCelda es "28/08/24"

                                // Elimina espacios en blanco y analiza la fecha con el formato específico
                                $dateTime = DateTime::createFromFormat('d/m/y', trim($excelTimestamp));

                                $excelTimestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateTime);
                                $fechaAsignacion = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelTimestamp);

                                $programada->FECHA_AGENDAMIENTO = $fechaAsignacion->format('Y-m-d');
                            } catch (\Exception $e) {
                                log::error($e);
                                DB::rollBack();
                                return 'Error al convertir fecha. revise columna N Fila ' . $row->getRowIndex();
                            }
                            break;
                        case 'P':
                            $jornada = $worksheet->getCell('O' . $row->getRowIndex())->getValue();
                            $programada->OBSERVACIONES = "JORNADA: " . $jornada . " OBSERVACIONES: " . $valorCelda;
                            break;
                        case 'B':
                            try {
                                $resultados = tbl_insp_cali::whereRaw("CONCAT(apellidos, ' ', nombres) = ?", [$valorCelda])
                                    ->first();
                                if ($resultados->aprendiz === 0) {
                                    $programada->TECNICO = $resultados->id . ". " . $valorCelda;
                                } else if ($resultados->aprendiz === 1) {
                                    $valor_orden = $worksheet->getCell('T' . $row->getRowIndex())->getValue();
                                    if ($valor_orden <> "" || $valor_orden <> null) {
                                        $macro = tbl_programacion_base::where('NUMERO_ORDEN', $valor_orden)->first();
                                        if(!$macro){$programada->TECNICO = "100. OFICINA";
                                            break;
                                        }
                                        $tecnico = tbl_insp_cali::where('id', $macro->ID_TECNICO)->first();
                                        $programada->TECNICO = $tecnico->id . ". " . $tecnico->apellidos . " " . $tecnico->nombres;
                                    } else {
                                        $valor_orden = $worksheet->getCell('S' . $row->getRowIndex())->getValue();
                                        $macro = tbl_programacion_base::where('NUMERO_ORDEN', $valor_orden)->first();
                                        if(!$macro){$programada->TECNICO = "100. OFICINA";
                                            break;
                                        }
                                        $tecnico = tbl_insp_cali::where('id', $macro->ID_TECNICO)->first();
                                        $programada->TECNICO = $tecnico->id . ". " . $tecnico->apellidos . " " . $tecnico->nombres;
                                    }
                                }else {
                                    $programada->TECNICO = "100. OFICINA";
                                    //DB::rollBack();
                                    //return 'Tecnico no encontrado. revise columna B Fila ' . $row->getRowIndex();
                                }
                            } catch (\Exception $e) {
                                log::error($e);
                                $programada->TECNICO = "100. OFICINA";
                                //DB::rollBack();
                                //return 'Error al consultar tecnico. revise columna B Fila ' . $row->getRowIndex();
                            }
                            break;
                    }
                }

                $executed = $this->programacionService->findExecuted($programada->CONTRATO,$programada->TIPO_TRABAJO,$programada->ORDEN_TRABAJO);

                if ($executed) {
                    continue;
                }

                $date = date('Y-m-d');
                $exist = tbl_programacion_contrato::where('CONTRATO', $programada->CONTRATO)
                    ->where('FECHA_AGENDAMIENTO','>=',$date)
                    ->first();

                if ($exist) {
                    $exist->delete();
                }
                $programada->save();
            }
            DB::commit();
            return true;
        } catch (Exception $e) {
            //$tabla->delete();
            DB::rollback();
            Log::error("Error al insertar datos: " . $e->getMessage()); // Registrar el error para depuración
            return 'Error al insertar datos: ' . $e->getMessage();
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
            DB::beginTransaction();
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
            $programacion->JORNADA = $request->data['JORNADA'];
            $programacion->HORA_INICIO = "06:59:00 a.m.";
            $programacion->HORA_FINAL = "04:59:00 p.m.";
            $programacion->id_programacion = $request->tabla;
            $programacion->plantilla = 1;
            $programacion->save();
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            log::error($e);
            return response()->json(['error' => 'No se pudo guardar registro. ' . $e->getMessage()], 422);;
        }
        return response()->json(['message' => 'Registro guardado correctamente', 'id' => $programacion->id], 200);
    }


    public function callCenterGdo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:xls,xlsx',
        ], [
            'archivo.required' => 'El archivo es requerido',
            'archivo.mimes' => 'El archivo debe ser un archivo excel',
            'archivo.file' => 'la entrada debe ser un archivo'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        date_default_timezone_set('America/Bogota');
        $file = IOFactory::load($request->file('archivo'));
        $worksheet = $file->getActiveSheet();

        // Validación rápida de cabeceras
        if (!$this->validacionGDO($worksheet)) {
            return response()->json(['error' => 'El archivo no cumple los criterios requeridos'], 422);
        }

        DB::beginTransaction();
        try {
            // Creamos la tabla padre indicando que NO ha terminado (finished = 0)
            $programacion = new tbl_programacion_usuario;
            $programacion->nombre = "Programación GDO " . Carbon::now()->format('Y-m-d');
            $programacion->id_usuario = Auth::id();
            $programacion->finished = 2;
            $programacion->mensaje = 1;
            $programacion->save();

            // Guardamos el archivo en storage temporalmente para que el Job lo lea
            $path = $request->file('archivo')->store('excel-imports-gdo');

            // Despachamos el trabajo en segundo plano
            ProcessCallCenterGdo::dispatch($path, $programacion->id, Auth::id());

            DB::commit();

            // Ya no devolvemos una URL porque el archivo apenas se va a procesar
            return response()->json([
                'message' => 'El archivo se está procesando con IA en segundo plano. Te notificaremos cuando termine.'
            ], 202);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al iniciar proceso: ' . $e->getMessage()], 500);
        }
    }

    private function validacionGDO($worksheet): bool
    {
        $cabeceras = [
            'A' => "NUMERO_ORDEN",
            'B' => "CONTRATO",
            'C' => "PRODUCTO",
            'D' => "NUMERO_SOLICITUD",
            'E' => "TIPO_SOLICITUD",
            'F' => "CEDULA",
            'G' => "NOMBRE",
            'H' => "DESC_DEPART",
            'I' => "DESC_LOCALIDAD",
            'J' => "BARRIO",
            'K' => "DIRECCION",
            'L' => "CONSECUTIVO_RUTA",
            'M' => "TELEFONO",
            'R' => "FECHA_ASIGNACION",
            'S' => "OBSERVACION_SOLICITUD"
        ];
        foreach ($cabeceras as $col => $valorEsperado) {
            if ($worksheet->getCell($col . '1')->getValue() !== $valorEsperado) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $row
     * @param $id_programacion
     * @param $scheduling
     * @param $observation
     * @return void
     *
     */


    private function insertBase($sheet)
    {

        try {
            foreach ($sheet->getRowIterator() as $row) {
                if ($row->getRowIndex() === 1) {
                    continue; // Saltar la primera fila (encabezados)
                }

                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $columnLetter = $cell->getColumn(); // Obtiene la letra de la columna (A, B, C, etc.)
                    $rowData[$columnLetter] = $cell->getValue(); // Usa la letra como clave del array
                }

                tbl_programacion_base::insertOrIgnore([
                    'NUMERO_ORDEN' => $rowData['A'],
                    'CONTRATO' => $rowData['B'],
                    'DESC_ESTADO_PROD' => $rowData['T'],
                    'NOMBRE' => $rowData['G'],
                    'DESC_LOCALIDAD' => $rowData['I'],
                    'BARRIO' => $rowData['J'],
                    'DIRECCION' => $rowData['K'],
                    'NOM_CATE' => $rowData['O'],
                    'ID_TIPO_TRABAJO' => $rowData['Q'],
                ]);

            }
            return true;
        } catch (\Exception $e) {
            log::error($e->getMessage());
            return false;
        }
    }


    public function ReAsignarProgramacion($fecha, ReAsignacion $programacionService)
    {
            // 1. Validar la fecha
            $validator = Validator::make(['fecha' => $fecha], [
                'fecha' => 'required|date_format:Y-m-d'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Formato de fecha inválido. Debe ser AAAA-MM-DD'], 400);
            }

            // 2. Llamar al servicio para que haga todo el trabajo
            $respuestaExcel = $programacionService->procesarYExportar($fecha);
            // 3. Verificar si hubo datos
        if (!$respuestaExcel) {
                return response()->json(['mensaje' => 'No hay programaciones para esta fecha.'], 404);
        }
        // 4. Retornar el Excel directamente al usuario
        return $respuestaExcel;
    }


}
