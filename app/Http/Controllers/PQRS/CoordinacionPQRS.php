<?php

namespace App\Http\Controllers\PQRS;

use App\Http\Controllers\Controller;
use App\Models\TblInspCali;
use App\Models\AsignadasQuejas;
use App\Services\PQRS\CoordinacionPQRSImportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PQRS\CoordinacionPQRSLectorHTML;
use App\Services\PQRS\CoordinacionUpdateRecepcion;
use App\Services\PQRS\CoordinacionPQRSExportService;
use App\Models\User;

class CoordinacionPQRS extends Controller
{
    public function __construct(
        private CoordinacionPQRSImportService $PQRSImportService,
    )
    {

    }

    public function index()
    {
        set_time_limit(400);

        $permiso_editar = false;
        $inspectores = TblInspCali::where('state', 1)->get();

        if (auth()->user()->can('coordinacion_pqrs')) {
            $permiso_editar = true;
        }
        $listaInspectoresArray = $inspectores->map(function ($i) {
            return "{$i->id}. {$i->apellidos} {$i->nombres}";
        })->toArray();
        array_unshift($listaInspectoresArray, '');

        // 1. Subconsulta para traer el técnico de la programación más reciente
        $tecnicoSubquery = DB::table('tbl_programacion_contratos')
            ->select('TECNICO') // <-- CAMBIA por tu columna real de técnico
            ->whereColumn('tbl_programacion_contratos.CONTRATO', 'asignadas_quejas.CONTRATO')
            ->orderBy('FECHA_AGENDAMIENTO', 'desc') // <-- CAMBIA por tu columna real de fecha
            ->limit(1);

        // 2. Subconsulta para traer la fecha de la programación más reciente
        $fechaSubquery = DB::table('tbl_programacion_contratos')
            ->select('FECHA_AGENDAMIENTO') // <-- CAMBIA por tu columna real de fecha
            ->whereColumn('tbl_programacion_contratos.CONTRATO', 'asignadas_quejas.CONTRATO')
            ->orderBy('FECHA_AGENDAMIENTO', 'desc') // <-- CAMBIA por tu columna real de fecha
            ->limit(1);

        // 3. Agregamos las subconsultas a la consulta principal
        $query = AsignadasQuejas::select("*")
            ->selectSub($tecnicoSubquery, 'TECNICO_AGENDADO')
            ->selectSub($fechaSubquery, 'FECHA_AGENDAMIENTO')
            ->where('estado', 1);



        $completeData = $query->get();

        CoordinacionUpdateRecepcion::Responsables($completeData);
        CoordinacionUpdateRecepcion::verificarYActualizarRecepcion($completeData);

        // --- NUEVO: Cálculo de fecha límite y días faltantes ---
        $hoy = Carbon::now()->startOfDay();
        foreach ($completeData as $queja) {
            if (!empty($queja->FECHA_ASIGNACION)) {

                // Limpiamos la fecha por si acaso trae hora y la parseamos correctamente
                $fechaCorta = explode(' ', trim($queja->FECHA_ASIGNACION))[0];

                try {
                    // Verificamos si la fecha viene con diagonales (d/m/Y)
                    if (strpos($fechaCorta, '/') !== false) {
                        $fechaAsignacion = Carbon::createFromFormat('d/m/Y', $fechaCorta)->startOfDay();

                    }
                    // Verificamos si viene con guiones y año al inicio (Y-m-d)
                    elseif (preg_match('/^\d{4}-/', $fechaCorta)) {
                        $fechaAsignacion = Carbon::createFromFormat('Y-m-d', $fechaCorta)->startOfDay();
                    }
                    // Si viene con guiones y día al inicio (d-m-Y)
                    else {
                        $fechaAsignacion = Carbon::createFromFormat('d-m-Y', $fechaCorta)->startOfDay();
                    }

                    $fechaLimite = $fechaAsignacion->copy()->addDays(4); // Suma 4 días

                    $queja->FECHA_LIMITE = $fechaLimite->format('Y-m-d');
                    // diffInDays con 'false' devuelve negativo si ya pasó la fecha
                    $queja->DIAS_FALTANTES = $hoy->diffInDays($fechaLimite, false);
                    $queja->save();
                } catch (\Exception $e) {
                    // Si la fecha es totalmente inválida y no se puede parsear
                    $queja->FECHA_LIMITE = null;
                    $queja->DIAS_FALTANTES = null;
                    $queja->save();
                    Log::warning("No se pudo parsear la fecha de asignación: " . $queja->FECHA_ASIGNACION . " Orden: " . $queja->NUMERO_ORDEN);
                }

            } else {
                $queja->FECHA_LIMITE = null;
                $queja->DIAS_FALTANTES = null;
            }
        }


        // $fechaActual = new DateTime();

        return view('pqrs.coordinacion', compact('inspectores', 'listaInspectoresArray', 'completeData','permiso_editar'));

    }


    /**
     * Datos de la tabla para el sondeo automático, que corre cada minuto.
     *
     * Devuelve la tabla entera sólo cuando ha cambiado algo. El cliente manda
     * la firma de lo que ya tiene pintado y, si coincide, se le responde con
     * un aviso de dos campos en vez de con todas las filas.
     *
     * La firma se calcula sobre el resultado ya armado, no sobre las marcas de
     * tiempo de las tablas: ninguna de las que intervienen tiene
     * `ON UPDATE CURRENT_TIMESTAMP`, así que fiarse de `updated_at` habría
     * dejado la tabla sin refrescar en silencio. Sobre el propio resultado no
     * hay forma de equivocarse: si cambia un solo dato, cambia la firma.
     *
     * Lo que se ahorra no es tanto la consulta —que con el índice de
     * tbl_programacion_contratos tarda 5 ms— como el trabajo del navegador:
     * sin esto, cada minuto se reconstruía la rejilla entera y había que
     * reponerle orden, filtros, selección y posición del scroll.
     */
    public function getDatosActualizados(Request $request)
    {

        // 1. Subconsulta para traer el técnico de la programación más reciente
        $tecnicoSubquery = DB::table('tbl_programacion_contratos')
            ->select('tecnico') // <-- CAMBIA por tu columna real de técnico
            ->whereColumn('tbl_programacion_contratos.contrato', 'asignadas_quejas.CONTRATO')
            ->orderBy('fecha_agendamiento', 'desc') // <-- CAMBIA por tu columna real de fecha
            ->limit(1);

        // 2. Subconsulta para traer la fecha de la programación más reciente
        $fechaSubquery = DB::table('tbl_programacion_contratos')
            ->select('fecha_agendamiento') // <-- CAMBIA por tu columna real de fecha
            ->whereColumn('tbl_programacion_contratos.contrato', 'asignadas_quejas.CONTRATO')
            ->orderBy('fecha_agendamiento', 'desc') // <-- CAMBIA por tu columna real de fecha
            ->limit(1);

        $completeData = AsignadasQuejas::select("*")
            ->selectSub($tecnicoSubquery, 'TECNICO_AGENDADO')
            ->selectSub($fechaSubquery, 'FECHA_AGENDAMIENTO')
            ->where('estado', 1)
            ->orderBy('id')
            ->get();

        CoordinacionUpdateRecepcion::Responsables($completeData);
        CoordinacionUpdateRecepcion::verificarYActualizarRecepcion($completeData);

        $hoy = Carbon::now()->startOfDay();
        foreach ($completeData as $queja) {
            if (!empty($queja->FECHA_ASIGNACION)) {
                $fechaCorta = explode(' ', trim($queja->FECHA_ASIGNACION))[0];

                try {
                    if (strpos($fechaCorta, '/') !== false) {
                        $fechaAsignacion = Carbon::createFromFormat('d/m/Y', $fechaCorta)->startOfDay();
                    } elseif (preg_match('/^\d{4}-/', $fechaCorta)) {
                        $fechaAsignacion = Carbon::createFromFormat('Y-m-d', $fechaCorta)->startOfDay();
                    } else {
                        $fechaAsignacion = Carbon::createFromFormat('d-m-Y', $fechaCorta)->startOfDay();
                    }

                    $fechaLimite = $fechaAsignacion->copy()->addDays(4);
                    $queja->FECHA_LIMITE = $fechaLimite->format('Y-m-d');
                    $queja->DIAS_FALTANTES = $hoy->diffInDays($fechaLimite, false);
                } catch (\Exception $e) {
                    $queja->FECHA_LIMITE = null;
                    $queja->DIAS_FALTANTES = null;
                }
            } else {
                $queja->FECHA_LIMITE = null;
                $queja->DIAS_FALTANTES = null;
            }
        }

        /* La firma va sobre el JSON definitivo. El orden de las filas se fija
           por clave primaria más arriba: sin un ORDEN estable, dos consultas
           idénticas podrían devolver las filas en distinto orden y la firma
           cambiaría sin que hubiera cambiado ningún dato. */
        $datos = $completeData->toArray();
        $firma = md5(json_encode($datos));

        if ($request->query('firma') === $firma) {
            return response()->json(['sin_cambios' => true, 'firma' => $firma]);
        }

        return response()->json(['data' => $datos, 'firma' => $firma]);
    }


    public function ImportOSF(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'Asignadas' => 'required|mimes:xlsx,xls',
            'Cerradas' => 'required|mimes:xlsx,xls',
            'archivos_html' => 'nullable|array',
            'archivos_html.*' => 'file|mimes:html',
        ], [
            'Asignadas.required' => 'El campo Asignadas es obligatorio.',
            'Asignadas.file' => 'El valor Asignadas debe ser un archivo.',
            'Asignadas.mimes' => 'El archivo debe ser de tipo XLS o XLSX.',
            'Cerradas.required' => 'El campo Cerradas es obligatorio.',
            'Cerradas.file' => 'El valor Cerradas debe ser un archivo.',
            'Cerradas.mimes' => 'El archivo debe ser de tipo XLS o XLSX.',
            'archivos_html.*.mimes' => 'Los archivos adicionales deben ser estrictamente de formato HTML.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        // Guardamos AMBOS archivos
        $pathAsignadas = $request->file('Asignadas')->store('excel-imports');
        $nameAsignadas = $request->file('Asignadas')->getClientOriginalName();

        $pathCerradas = $request->file('Cerradas')->store('excel-imports');
        $nameCerradas = $request->file('Cerradas')->getClientOriginalName();

        if ($request->hasFile('archivos_html')) {
            foreach ($request->file('archivos_html') as $file) {
                $nombreHtml = $file->getClientOriginalName();
                // storeAs con el disco local por defecto guarda en storage/app/
                $file->storeAs('PQRS_HTML', $nombreHtml);
            }
        }

        // Pasamos ambos archivos al servicio
        $response = $this->PQRSImportService->processDualFiles(
            $pathAsignadas, $nameAsignadas,
            $pathCerradas, $nameCerradas
        );

        if (isset($response['errors'])) {
            return response()->json(['errors' => $response['errors']], 422);
        }

        return response()->json(['message' => $response['message']], 202);


    }

    public function updateAsignado(Request $request)
    {
        $movitos_PQR = [
            '',
            'Apelacion',
            'Atencion brindada',
            'Cobros ocasionados',
            'Deja daños',
            'Demora prestacion servicio',
            'Error legalizacion',
            'Inconforme con el proceso',
            'Incumplimiento cita',
            'Presentacion personal',
            'Solicitud de dineros',
            'No aplica'];

        $op_recepcion = [
            '',
            'ACCEDE',
            'NO ACCEDE',
            'GDW',
            'NO PROCEDENTE'
        ];
        $queja = AsignadasQuejas::where('NUMERO_ORDEN', $request->orden)
            ->where('CONTRATO', $request->contrato)
            ->first();

        if(auth()->user()->hasPermissionTo('coordinacion_pqrs')){
            // Agregamos las nuevas columnas permitidas
            $camposPermitidos = [
                'ASIGNADO', 'RESPONSABLE', 'RECEPCION', 'OBSERVACION_GESTION',
                'CODIGO_AUTORIZACION','MOTIVO_DE_PQR','FECHA_SOLICITUD_CIERRE',
                'INSTRUCCIONES_CAMPO','OBSERVACION_SUPERVISOR'
            ];

        }else{
            // Agregamos las nuevas columnas permitidas
            $camposPermitidos = [
                'OBSERVACION_SUPERVISOR'
            ];
        }



        if ($queja && in_array($request->campo, $camposPermitidos)) {

            $dataToUpdate = [$request->campo => $request->valor];
            $fechaAsignado = null;
            $nombreSupervisor = null;
            $fechaExtra = null; // Variable para devolver fechas automáticas (Recepción o Respuesta) al frontend

            // 1. Lógica para Inspectores (ASIGNADO o RESPONSABLE)
            if (in_array($request->campo, ['ASIGNADO', 'RESPONSABLE']) && !empty($request->valor)) {

                $partes = explode('.', $request->valor);
                $inspectorId = trim($partes[0]);

                if (!is_numeric($inspectorId)) {
                    return response()->json([
                        'error' => 'Formato incorrecto. Debe seleccionar un inspector válido de la lista.',
                        'revert' => true
                    ], 422);
                }

                $inspector = TblInspCali::with('supervisor')->where('id', $inspectorId)->first();

                if (!$inspector) {
                    return response()->json([
                        'error' => 'El inspector seleccionado no existe en la base de datos.',
                        'revert' => true
                    ], 422);
                }

                $cadenaOficial = "{$inspector->id}. {$inspector->apellidos} {$inspector->nombres}";

                if ($request->valor !== $cadenaOficial) {
                    return response()->json([
                        'error' => 'El texto ingresado fue modificado y no coincide con la lista.',
                        'revert' => true
                    ], 422);
                }


                if ($request->campo === 'ASIGNADO') {
                    $fechaAsignado = date('Y-m-d');
                    $dataToUpdate['FECHA_ASIGNADO'] = $fechaAsignado;

                    $inspectoresExcluidos = [100];

                    if (in_array((int)$inspectorId, $inspectoresExcluidos)) {
                        $nombreSupervisor = null;
                    } else {
                        if ($inspector->supervisor) {
                            $nombreSupervisor = $inspector->supervisor->name;
                        } else {
                            $nombreSupervisor = 'Sin supervisor';
                        }
                    }

                    $dataToUpdate['SUPERVISOR'] = $nombreSupervisor;
                }

            } elseif ($request->campo === 'ASIGNADO' && empty($request->valor)) {
                $dataToUpdate['FECHA_ASIGNADO'] = null;
                $dataToUpdate['SUPERVISOR'] = null;
            }

            if($request->campo === 'RECEPCION'){
                if(!in_array($request->valor,$op_recepcion)){
                    return response()->json([
                        'error' => 'Opción no válida',
                        'revert' => true
                    ], 422);
                }
            }

            if($request->campo === 'MOTIVO_DE_PQR'){

                if(!in_array($request->valor,$movitos_PQR)){
                    return response()->json([
                        'error' => 'El motivo seleccionado no es válido',
                        'revert' => true
                    ], 422);
                }
            }

            if($request->campo === 'FECHA_SOLICITUD_CIERRE'){

                if(!empty($request->valor)){
                    try {
                        // 1. Intentamos parsear la fecha con el formato específico
                        $fechaInput = Carbon::createFromFormat('Y-m-d', $request->valor)->startOfDay();

                        // 2. Definimos el límite (Hoy menos 2 días)
                        $fechaLimite = Carbon::today()->subDays(2);

                        // 3. Validamos si la fecha ingresada es menor al límite
                        if ($fechaInput->lt($fechaLimite)) {
                            return response()->json([
                                'revert' => true,
                                'error' => 'La fecha no puede ser anterior a ' . $fechaLimite->format('d/m/Y')
                            ], 422);
                        }

                    } catch (\Exception $e) {
                        // Si Carbon falla al parsear, el formato es incorrecto
                        return response()->json([
                            'revert' => true,
                            'error' => 'Formato de fecha inválido. Use YYYY-MM-DD'
                        ], 422);
                    }
                }
            }

            if ($request->campo === 'RECEPCION') {
                if (trim(strtoupper($request->valor)) === 'GDW' || trim(strtoupper($request->valor)) === 'ACCEDE' || trim(strtoupper($request->valor)) === 'NO ACCEDE') {
                    $fechaExtra = date('Y-m-d');
                    $dataToUpdate['FECHA_RECEPCION'] = $fechaExtra;
                } else {
                    $fechaExtra = null;
                    $dataToUpdate['FECHA_RECEPCION'] = null;
                }
            }


            if ($request->campo === 'CODIGO_AUTORIZACION') {
                if (!empty($request->valor)) {
                    if (!is_numeric($request->valor)) {
                        return response()->json([
                            'error' => 'El código de autorización debe contener solo números.',
                            'revert' => true
                        ], 422);
                    }
                    $fechaExtra = date('Y-m-d');
                    $dataToUpdate['FECHA_RESPUESTA'] = $fechaExtra;
                } else {
                    $fechaExtra = null;
                    $dataToUpdate['FECHA_RESPUESTA'] = null;
                }
            }

            $queja->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'fecha_asignado' => $fechaAsignado,
                'supervisor' => $nombreSupervisor,
                'fecha_extra' => $fechaExtra // Se usa para FECHA_RECEPCION o FECHA_RESPUESTA según el caso
            ]);
        }

        return response()->json(['error' => 'Registro no encontrado o campo no permitido'], 404);
    }

    // --- NUEVO MÉTODO PARA HISTÓRICO ---
    public function getHistorico(Request $request)
    {
        $query = AsignadasQuejas::select("*")->where('estado', 0);

        // Filtro por Contrato
        if ($request->filled('contrato')) {
            $query->where('CONTRATO', $request->input('contrato'));
        }

        // Filtro por Orden
        if ($request->filled('orden')) {
            $query->where('NUMERO_ORDEN', $request->input('orden'));
        }

        // Filtro por Rango de Fechas (Basado en FECHA_ASIGNACION)
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            // Asumiendo que FECHA_ASIGNACION es varchar en formato DD/MM/YYYY, esto puede requerir ajuste
            // Si FECHA_ASIGNACION está guardada como DATE en mysql, funciona directo.
            // Si es un varchar 'dd/mm/yyyy hh:mm:ss', se debe hacer una conversión o usar LIKE.
            // Asumimos que limpiaste las fechas a Y-m-d o están parseables.
            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');

            // Si las fechas en BD están en formato DD/MM/YYYY
            // Lo ideal es tenerlas en Y-m-d, pero si no, tendrás que ajustar la consulta.
            $query->whereBetween(DB::raw('STR_TO_DATE(FECHA_LEGALIZACION, "%d/%m/%Y")'), [$fechaInicio, $fechaFin])
                ->orWhereBetween('FECHA_LEGALIZACION', [$fechaInicio, $fechaFin]);
        }

        $datosHistorico = $query->get();

        return response()->json([
            'success' => true,
            'data' => $datosHistorico
        ]);
    }

    // MÉTODO PARA EXPORTAR A GDW ---
    public function exportarGDW(Request $request)
    {
        // Validamos que la fecha sea obligatoria SOLAMENTE si el checkbox no fue enviado
        $request->validate([
            'exportar_pendientes' => 'nullable',
            'fecha_exportacion' => 'required_without:exportar_pendientes|nullable|date'
        ]);

        // Base de la consulta: Estado 1 y que tenga técnico asignado
        $query = AsignadasQuejas::where('estado', 1)
            ->whereNotNull('ASIGNADO')
            ->where('ASIGNADO', '!=', '');

        // Verificamos si el usuario marcó el checkbox de "Pendientes"
        if ($request->has('exportar_pendientes') && $request->exportar_pendientes == 'on') {

            // Si está marcado, buscamos donde RECEPCION sea nulo o vacío
            $query->where(function($q) {
                $q->whereNull('RECEPCION')
                    ->orWhere('RECEPCION', '');
            });

        } else {
            // Si NO está marcado, filtramos normalmente por la fecha
            $fechaInput = $request->input('fecha_exportacion');
          // $fechaDmY = Carbon::parse($fechaInput)->format('d/m/Y');

            $query->where(function($q) use ($fechaInput) {
                $q->where('FECHA_ASIGNADO', 'LIKE', $fechaInput . '%');
                  //  ->orWhere('FECHA_ASIGNACION', 'LIKE', $fechaDmY . '%');
            });
        }

        // Ejecutamos la consulta
        $datosGDW = $query->get();

        // Verificamos que haya datos antes de generar los archivos
        if ($datosGDW->isEmpty()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontraron registros para exportar con los criterios seleccionados.'
            ]);
        }

        // Delegamos TODA la lógica de creación de archivos al Servicio
        $resultadoArchivos = CoordinacionPQRSLectorHTML::CrearArchivos($datosGDW);

        // Agregamos la cantidad encontrada para mostrarla en la alerta del Frontend
        $resultadoArchivos['cantidad_encontrada'] = $datosGDW->count();

        return response()->json($resultadoArchivos);
    }

    public function getSupervisores()
    {
        // Buscamos usuarios activos con el rol de supervisor
        // Ajusta 'Supervisor' al nombre exacto de tu rol en la BD
        $supervisores = User::role('Supervisor')
            ->where('state', 1)
            ->get(['id', 'name']);

        return response()->json($supervisores);
    }

    public function exportarSupervisorExcel(Request $request)
    {
        $nombreSupervisor = $request->input('supervisor_name');

        // Consulta de datos
        $quejas = AsignadasQuejas::where('estado', 1)
            ->where('SUPERVISOR', $nombreSupervisor)
            ->where(function($q) {
                $q->whereNull('RECEPCION')
                    ->orWhere('RECEPCION', '')
                    ->orWhere('RECEPCION', 'GDW');
            })
            ->select('CONTRATO', 'ASIGNADO', 'OBSERVACION_SOLICITUD', 'INSTRUCCIONES_CAMPO','OBSERVACION_SUPERVISOR')
            ->get();

        if ($quejas->isEmpty()) {
            return response()->json(['error' => "No se encontraron registros para el supervisor: $nombreSupervisor"], 404);
        }

        // Delegamos la lógica al servicio
        try {
            $urlFirmada = CoordinacionPQRSExportService::generarExcelSupervisor($quejas, $nombreSupervisor);

            return response()->json([
                'success' => true,
                'downloadUrl' => $urlFirmada
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => "Error al generar el archivo: " . $e->getMessage()], 500);
        }
    }

    public function exportarHistoricoExcel(Request $request)
    {
        // Recibimos la matriz de datos desde Handsontable
        $datosTabla = $request->input('datos_tabla');

        if (empty($datosTabla)) {
            return response()->json(['error' => "No se recibieron datos para exportar."], 400);
        }

        try {
            // Pasamos la matriz directamente al servicio
            $urlFirmada = CoordinacionPQRSExportService::generarExcelDesdeMatriz($datosTabla);

            return response()->json([
                'success' => true,
                'downloadUrl' => $urlFirmada
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => "Error al generar Excel: " . $e->getMessage()], 500);
        }
    }
}
