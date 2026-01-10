<?php

namespace App\Http\Controllers;


use App\Models\Programacion\tbl_programacion_base;
use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Programacion\tbl_programacion_usuario;
use App\Models\tbl_insp_cali;
use App\Models\User;
use App\Services\ExtraerFechas;
use App\Services\Programacion\ProgramacionContratoService;
use App\Services\Programacion\ProgramacionImportService;
use App\Services\Programacion\ProgramacionUsuarioService;
use Carbon\Carbon;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class ProgramacionController extends Controller
{

    public function __construct(
        private ProgramacionContratoService $programacionContratoService,
        private ProgramacionUsuarioService  $programacionUsuarioService,
        private ProgramacionImportService   $programacionImportService
    )
    {

    }


    public function index()
    {
        // Llama al método del servicio para obtener los datos
        $resultado = $this->programacionUsuarioService->obtenerProgramacionesUsuario(Auth::user());

        // Asignación de los resultados
        $datos = $resultado['terminadas'];
        $temp = $resultado['enCurso'];

        if (!is_null($temp)) {
            session()->flash('warning', 'Ya tienes una tabla de programación en curso ¿Deseas continuar?');
            return view('programacion.index', compact('datos', 'temp'));
        }

        return view('programacion.index', compact('datos'));
    }

    public function create()
    {
        // Obtén el usuario autenticado
        $user = Auth::user();

        // Usa el servicio para crear/verificar una programación
        $response = $this->programacionUsuarioService->crearNuevaProgramacion($user);

        // Manejo si ocurre un error en la creación
        if (isset($response['error'])) {
            Session::flash('error', $response['error']);
            return redirect()->route('programacion.index');
        }

        // Si ya existe una programación activa y debe redirigirse
        if (isset($response['redirect']) && $response['redirect']) {
            return $this->index();
        }

        // Retorna la vista de creación con los datos necesarios
        return view('programacion.create', [
            'tecnicos' => $response['tecnicos'],
            'user' => $response['user'],
            'programacion' => $response['programacion'],
        ]);
    }

    public function show(Request $request, $id)
    {
        $action = $request->query('action');
        $id = (int)$id;
        // Llamamos al servicio para manejar toda la lógica de la programación
        $response = $this->programacionUsuarioService->obtenerDetalleProgramacion($id, $action);

        // Manejo de errores
        if (isset($response['error'])) {
            session()->flash('error', $response['error']);
            return redirect()->route('programacion.index');
        }

        // Variables para la vista
        $tecnicos = $response['tecnicos'];
        $user = $response['user'];
        $programacion = $response['programacion'];
        $tabla = $response['tabla'];

        // Renderizamos vistas según la acción
        if ($action === 'view') {
            $view = true; // Indicador para vista
            return view('programacion.create', compact('tecnicos', 'user', 'programacion', 'tabla', 'view'));
        }

        if ($action === 'edit') {
            return view('programacion.create', compact('tecnicos', 'user', 'programacion', 'tabla'));
        }

        // Si no hay una acción válida, redirigimos al índice
        return redirect()->route('programacion.index');
    }

    public function erase(int $id): JsonResponse
    {
        // Llama al servicio para manejar la lógica
        $response = $this->programacionUsuarioService->eliminarProgramacion($id);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']], 500);
        }

        return response()->json(['message' => $response['message']]);
    }

    public function busqueda($contrato): ?\Illuminate\Http\JsonResponse
    {
        // Llamar al servicio para manejar la lógica
        $resultado = $this->programacionContratoService->buscarContratoBase($contrato);

        // Si no se encuentra nada, devolver null
        if ($resultado === null) {
            return null;
        }

        // Si hay un error, devolverlo como respuesta JSON con un código 422
        if (isset($resultado['error'])) {
            return response()->json(['movilidad' => $resultado['error']]);
        }

        // Retornar la información del contrato como respuesta JSON
        return response()->json($resultado);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {

        // Llama al servicio para manejar la lógica.
        $response = $this->programacionContratoService->crearProgramacionContrato($request->all());

        // Si hay un error, devolver el mensaje con el código HTTP apropiado.
        if ($response['error']) {
            return response()->json($response);
        }

        // Devuelve la respuesta de éxito.
        return response()->json($response);
    }

    /**
     * Actualiza un contrato de programación.
     *
     * @param int $id ID del contrato.
     * @param Request $request
     * @return JsonResponse
     */
    public function update($id, Request $request): JsonResponse
    {
        // Extraer campo y valor de la solicitud
        $campo = $request->input('propiedad');
        $valor = $request->input('valor');

        // Llamar al servicio para manejar la lógica de actualización
        $response = $this->programacionContratoService->actualizarProgramacionContrato($id, $campo, $valor);

        // Manejar la respuesta del servicio
        if ($response['error']) {
            return response()->json(['error' => $response['message']], 422);
        }

        return response()->json(['message' => $response['message']]);
    }

    /**
     * Elimina un contrato de programación dado su ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        // Extraer el ID de la solicitud
        $id = $request->input('data');

        // Llamar al servicio para manejar la lógica de eliminación
        $response = $this->programacionContratoService->eliminarProgramacionContrato($id);

        // Manejar la respuesta del servicio
        if ($response['error']) {
            return response()->json(['error' => $response['message']], 500);
        }

        return response()->json(['message' => $response['message']]);
    }

    /**
     * Finaliza una programación.
     *
     * @param int $id ID de la programación.
     * @return JsonResponse
     */
    public function finish(int $id): JsonResponse
    {
        // Llamar al servicio para finalizar la programación
        $response = $this->programacionContratoService->finalizarProgramacion($id);

        // Manejar la respuesta del servicio
        if ($response['error']) {
            return response()->json(['error' => $response['message']], 500);
        }

        return response()->json(['ok' => $response['message']]);
    }

    public function detalles()
    {
        $tecnicos = tbl_insp_cali::where('state', '1')->get();
        return view('programacion.ver', compact('tecnicos'));
    }

    public function base(Request $request): JsonResponse
    {
        $valorCheckBox = $request->input('check_estado5');
        $request->validate([
            'archivo' => 'required|file|mimes:xls,xlsx',
        ], [
            'archivo.required' => 'El campo archivo es obligatorio.',
            'archivo.file' => 'El valor debe ser un archivo.',
            'archivo.mimes' => 'El archivo debe ser de tipo XLS o XLSX.',
        ]);

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
        $name_file = $request->file('archivo')->getClientOriginalName();
        $response = $this->programacionImportService->processFile($path, $request->type, $name_file);

        if (isset($response['errors'])) {
            return response()->json(['errors' => $response['errors']], 422);
        }

        return response()->json(['message' => $response['message']]);

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

            $plantilla = $plantilla->orderBy('TECNICO')->get();
            $busqueda = $busqueda->orderBy('TECNICO')->get();


            // Agregar primero los registros de plantilla
            $finalData = [];

            foreach ($plantilla as $registro) {
                $finalData[] = $registro;
            }

            // Luego los restantes, descartando duplicados si algún registro de plantilla también está en $busqueda
            $uniqueKeys = [];

            foreach ($plantilla as $itemPlantilla) {
                $keyP = $itemPlantilla->ORDEN_TRABAJO . $itemPlantilla->FECHA_AGENDAMIENTO . $itemPlantilla->PORQUE_PROGRAMO;
                $uniqueKeys[] = $keyP;
            }

            foreach ($busqueda as $item) {
                $key = $item->ORDEN_TRABAJO . $item->FECHA_AGENDAMIENTO . $item->PORQUE_PROGRAMO;
                if (!in_array($key, $uniqueKeys)) {
                    $finalData[] = $item;
                }
            }

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
                $hora_inicio = '06:59:00 a.m.';
                $hora_final = $item[18];
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
            if (!file_exists($carpetaTmp)) {
                mkdir($carpetaTmp, 0777, true);
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
        $mpdf = new Mpdf([
                'orientation' => 'L' // L = Landscape (horizontal)
            ]
        );
        // Limpia y construye el nombre
        $nombreLimpio = preg_replace('/[^A-Za-z0-9_\-]/', '_', $supervisor->nombre);
        $fileName = 'reporte_' . $nombreLimpio . " " . ($fechaInicio ? str_replace('-', '_', $fechaInicio) : '')
            . ($fechaFin ? ('_' . str_replace('-', '_', $fechaFin)) : '') . '.pdf';
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
            'Quien programo', 'Tecnico', 'Hora  inicio', 'Hora final'
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
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($archivos as $archivo) {
            $zip->addFile($archivo, basename($archivo));
        }
        $zip->close();
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
                    $programada->HORA_INICIO = "06:59:00 a.m.";
                    $programada->HORA_FINAL = "11:59:00 a.m.";
                } elseif (strpos($worksheet->getCell('O' . $row->getRowIndex())->getValue(), "TARDE") !== false) {
                    $programada->HORA_INICIO = "11:59:00 a.m.";
                    $programada->HORA_FINAL = "04:59:00 p.m.";
                } elseif (strpos($worksheet->getCell('O' . $row->getRowIndex())->getValue(), "TRANSCURSO DEL DIA") !== false) {
                    $programada->HORA_INICIO = "06:59:00 a.m.";
                    $programada->HORA_FINAL = "04:59:00 p.m.";
                } else {
                    // Valores por defecto si no se cumple ninguna condición
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
                                if ($resultados) {
                                    $programada->TECNICO = $resultados->id . ". " . $valorCelda;
                                } else {
                                    DB::rollBack();
                                    return 'Tecnico no encontrado. revise columna B Fila ' . $row->getRowIndex();
                                }
                            } catch (\Exception $e) {
                                log::error($e);
                                DB::rollBack();
                                return 'Error al consultar tecnico. revise columna B Fila ' . $row->getRowIndex();
                            }
                            break;
                    }
                }

                $executed = $this->programacionService->findExecuted($programada->CONTRATO, $programada->TIPO_TRABAJO,$programada->ORDEN_TRABAJO);

                if ($executed) {
                    continue;
                }
                $date = date('Y-m-d');
                $exist = tbl_programacion_contrato::where('CONTRATO', $programada->CONTRATO)
                    ->where('FECHA_AGENDAMIENTO', '>=', $date)
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
            $programacion->HORA_INICIO = $request->data['HORA_INICIO'];
            $programacion->HORA_FINAL = $request->data['HORA_FINAL'];
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
        $date = Datetime::createFromFormat('Y/m/d', Carbon::now()->format('Y/m/d'));
        $worksheet = $file->getActiveSheet();
        $indicador = $this->validacionGDO($worksheet);

        if (!$indicador) {
            return response()->json(['error' => 'El archivo no cumple los criterios requeridos'], 422);
        }

        $worksheet->setCellValue('AB1', 'Resultado');

        DB::beginTransaction();
        $programacion = new tbl_programacion_usuario;
        $programacion->nombre = "Programación GDO " . Carbon::now()->format('Y-m-d');
        $programacion->id_usuario = Auth::id();
        $programacion->finished = 1;
        $programacion->mensaje = 1;
        $programacion->save();

        foreach ($worksheet->getRowIterator() as $row) {
            if ($row->getRowIndex() === 1) {
                continue;
            }
            $rango = 'A' . $row->getRowIndex() . ':AA' . $row->getRowIndex();

            $string = $worksheet->getCell('S' . $row->getRowIndex())->getValue();
            if ($string == "" || $string == null) {
                continue;
            }
            // Obtienes el valor numéric FECHA de la celda
            $valorNumericoExcel = $worksheet->getCell('R' . $row->getRowIndex())->getValue();

            // Usas la función de la librería para convertirlo
            $fechaComoDateTime = Date::excelToDateTimeObject($valorNumericoExcel);
            $fechas = new ExtraerFechas();
            //servicio para encontrar fechas en la columna de observación
            $array = $fechas->findDates($string, $fechaComoDateTime->format('Y-m-d'), $row->getRowIndex());


            //validación de array y que tengan objetos tipo DATE TIME
            if (is_array($array) && count($array) > 0 && collect($array)->every(fn($item) => $item instanceof DateTime)) {
                $fechaArray = Carbon::instance($array[0]);
                $fechaComparar = Carbon::instance($date);
                $diferenciaMeses = $fechaComparar->diffInMonths($fechaArray);


                if (count($array) >= 2) {
                    $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Hay dos o mas fechas, verificar ');
                    $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFECC862');
                } else if ($date->format('Y-m-d') > $array[0]->format('Y-m-d')) {
                    $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Fecha menor al actual, verificar ' . $array[0]->format('Y-m-d'));
                    $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFECC862');
                } else if ($diferenciaMeses > 4) {
                    $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Fecha de programación supera limite de diferencia ' . $array[0]->format('Y-m-d'));;
                    $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF5353');
                } else {
                    //obtener datos de la fila para mandar a inserción
                    $filaArray = $worksheet->rangeToArray(
                        'A' . $row->getRowIndex() . ':AA' . $row->getRowIndex(),
                        null,      // default null for empty cells
                        true,      // calculate formulas
                        false,     // do not format values
                        true      // return associative array (false: numeric)
                    );
                    $valoresFila = $filaArray[$row->getRowIndex()];
                    //funcion para insertar datos
                    try {
                        $resultado = $this->insertarDatosGDO($valoresFila, $programacion->id, $array[0]->format('Y-m-d'), $string);
                        if ($resultado == 1) {
                            $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Programado para ' . $array[0]->format('Y-m-d'));
                            $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF6FF658');
                        } else if ($resultado == 2) {
                            $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Error al programar, intente manualmente ' . $array[0]->format('Y-m-d'));
                            $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFABAB');
                        } else if ($resultado == 0) {
                            $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Ya existe una programación para esta orden');
                            $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFECA2CE');
                        } else if ($resultado == 3) {
                            $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Orden Ya Ejecutada');
                            $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF333333');
                        }
                    } catch (\Exception $e) {
                        // No afecta a las otras filas, solo esta
                        $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Error inesperado: ' . $e->getMessage());
                        $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFABAB');
                    }


                }
            } else
                if ($array == 1000) {
                    $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Error en Interpretación, revisar');
                    $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFABAB');
                } else {
                    $worksheet->setCellValue('AB' . $row->getRowIndex(), 'Registro no Valido');
                }
        }
        // Guardar el archivo modificado en almacenamiento temporal
        $nombreArchivo = 'Resultados Programadas GDO ' . date('Y-m-d H-i-s') . '.xlsx';
        $ruta = storage_path('app/uploads/' . $nombreArchivo);
        $writer = new Xlsx($file);
        $writer->save($ruta);

        $url = url()->temporarySignedRoute(
            'descargar.archivo', // Usa la nueva ruta genérica
            now()->addMinutes(5), // Expiración en 10 minutos
            ['file' => $nombreArchivo] // Archivo como parámetro
        );
        DB::commit();
        return response()->json(['url' => $url,
            'message' => 'Archivo procesado correctamente']);
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
    private
    function insertarDatosGDO($row, $id_programacion, $scheduling, $observation)
    {
        // verificar si ya existe la programación
        $exist = tbl_programacion_contrato::where('CONTRATO', $row['B'])
            //->where('ORDEN_TRABAJO', $row['A'])
            ->where('FECHA_AGENDAMIENTO', '>=', $scheduling)
            ->exists();

        $executed = $this->programacionService->findExecuted($row['B'], $row['Q'],$row['A']);

        if ($exist) {
            return 0;
        }
        if ($executed) {
            return 3;
        }
        // Insertar los datos si es que ya no existe
        try {
            $registro = new tbl_programacion_contrato();
            $registro->CONTRATO = $row['B'];
            $registro->TIPO_TRABAJO = $row['Q'];
            $registro->FECHA = date('Y-m-d');
            $registro->CELULAR = '-';
            $registro->NOMBRE_USUARIO = $row['G'];
            $registro->ORDEN_TRABAJO = $row['A'];
            $registro->DIRECCION = $row['K'];
            $registro->BARRIO = $row['J'];
            $registro->CIUDAD = $row['I'];
            if ($row['T'] == 'Activo') {
                $registro->ACTIVA = 'Si';
                $registro->SUSPENDIDO = 'No';
            } else {
                $registro->ACTIVA = 'No';
                $registro->SUSPENDIDO = 'Si';
            }
            $registro->CATEGORIA = $row['O'];
            $registro->FECHA_AGENDAMIENTO = $scheduling;
            $registro->OBSERVACIONES = $observation;
            $registro->PORQUE_PROGRAMO = 'PROGRAMACION GDO';
            $registro->TECNICO = '100. OFICINA';
            $registro->HORA_INICIO = "06:59:00 a.m.";
            $registro->HORA_FINAL = "04:59:00 p.m.";
            $registro->id_programacion = $id_programacion;
            $registro->mensaje = 1;
            $registro->plantilla = 0;
            $registro->EJECUTADA = 0;
            $registro->save();
            return 1;
        } catch (\Exception $e) {
            log::error($e->getMessage());
            return 2;
        }


    }

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


}
