<?php

namespace App\Http\Controllers\PQRS;

use App\Http\Controllers\Controller;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use App\Models\tbl_insp_cali;
use App\Models\Zonificacion\tbl_localidades_sede;
use App\Models\Zonificacion\TblGrupo;
use App\Models\Zonificacion\TblSubgrupo;
use App\Models\asignadas_quejas;
use App\Services\PQRS\CoordinacionPQRSImportService;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\tbl_quejas_contrato;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use App\Services\PQRS\CoordinacionPQRSLectorHTML;
class CoordinacionPQRS extends Controller
{
    public function __construct(
        private CoordinacionPQRSImportService $PQRSImportService,
        private CoordinacionPQRSLectorHTML $PQRSLectorHTML
    )
    {

    }

    public function index()
    {
        set_time_limit(400);

        $inspectores = tbl_insp_cali::where('state', 1)->get();
        $groups = TblGrupo::all();
        $subgroups = TblSubgrupo::all();
        // Armar array para el Dropdown (id. apellidos nombres)

        $listaInspectoresArray = $inspectores->map(function ($i) {
            return "{$i->id}. {$i->apellidos} {$i->nombres}";
        })->toArray();
        array_unshift($listaInspectoresArray, '');

        $query = asignadas_quejas::select("*")->where('estado', 1);
        $completeData = $query->get();

        $this->Responsables($completeData);
        $this->verificarYActualizarRecepcion($completeData);

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
        // traemos las sedes para crear el selector en el modal de impresion masiva
        $sedes = tbl_localidades_sede::all();

        // $fechaActual = new DateTime();

        return view('pqrs.coordinacion', compact('inspectores', 'listaInspectoresArray', 'groups', 'subgroups', 'completeData', 'sedes'));

    }

    private function Responsables($completeData)
    {
        $todos_inspectores = tbl_insp_cali::all();
        $tipos_trabajo_rp = array("10444", "12161");
        $tipos_trabajo_sa = array("12163", "12164");

        $contratosConPrefijo = $completeData->pluck('CONTRATO')
            ->filter()
            ->unique()
            ->map(fn($item) => ':' . $item);

        // 2. Realizamos la consulta ordenando por fecha descendente
        $bitacoras = tbl_bitacora_contrato::whereIn('CONTRATO', $contratosConPrefijo)
            ->select('CONTRATO', 'TIPO_TRABAJO', 'CC_OPERARIO', 'FECHA')
            ->orderBy('FECHA', 'desc') // Los más recientes primero
            ->get()
            // 3. Filtramos la colección para dejar solo un registro por contrato
            ->unique('CONTRATO')
            // 4. Agrupamos por contrato para mantener tu estructura original
            ->groupBy('CONTRATO');

        // Mapeamos responsable en los datos
        foreach ($completeData as $queja) {
            // Si el responsable ya está guardado en BD, lo dejamos así
            // Si está vacío, intentamos buscarlo y actualizar la BD
            if (empty($queja->RESPONSABLE) && $queja->CONTRATO && $queja->TIPO_TRABAJO_CIERRE_ULTIMA) {
                if (in_array($queja->TIPO_TRABAJO_CIERRE_ULTIMA, $tipos_trabajo_rp)) {
                    $tipo_trabajo = "RP ".$queja->TIPO_TRABAJO_CIERRE_ULTIMA;
                } elseif (in_array($queja->TIPO_TRABAJO_CIERRE_ULTIMA, $tipos_trabajo_sa)) {
                    $tipo_trabajo = "SA " . $queja->TIPO_TRABAJO_CIERRE_ULTIMA;
                } elseif ($queja->TIPO_TRABAJO_CIERRE_ULTIMA == "12162") {
                    $tipo_trabajo = "RN " . $queja->TIPO_TRABAJO_CIERRE_ULTIMA;
                }
                //dd($tipo_trabajo);
                $quejaBitacoras = $bitacoras->get(":".$queja->CONTRATO);

                if ($quejaBitacoras) {
                    $bitacora = $quejaBitacoras->firstWhere('TIPO_TRABAJO', $tipo_trabajo);
                    if ($bitacora && $bitacora->CC_OPERARIO) {
                        $inspector = $todos_inspectores->firstWhere('cedula', $bitacora->CC_OPERARIO);
                        if ($inspector) {
                            $responsableFormat = "{$inspector->id}. {$inspector->apellidos} {$inspector->nombres}";
                            $queja->RESPONSABLE = $responsableFormat;
                            // Guardamos el responsable encontrado para futuras consultas
                            $queja->save();
                        }
                    }
                }
            }
        }
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
        $queja = asignadas_quejas::where('NUMERO_ORDEN', $request->orden)
            ->where('CONTRATO', $request->contrato)
            ->first();

        // Agregamos las nuevas columnas permitidas
        $camposPermitidos = [
            'ASIGNADO', 'RESPONSABLE', 'RECEPCION', 'OBSERVACION_GESTION',
            'CODIGO_AUTORIZACION','MOTIVO_DE_PQR','FECHA_SOLICITUD_CIERRE'
        ];

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

                $inspector = tbl_insp_cali::with('supervisor')->where('id', $inspectorId)->first();

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

                    $inspectoresExcluidos = [100, 101, 102];

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

    // --- NUEVO MÉTODO PARA VERIFICAR RECEPCIÓN AUTOMÁTICA ---
    private function verificarYActualizarRecepcion($completeData)
    {
        // Traemos las quejas cruzadas para optimizar
        $ordenes = $completeData->pluck('NUMERO_ORDEN')->filter()->unique();
        $quejasContrato = tbl_quejas_contrato::whereIn('ORDEN_TRABAJO', $ordenes)
            ->get(['CONTRATO', 'ORDEN_TRABAJO', 'RESULTADO_CIERRE'])
            ->groupBy('ORDEN_TRABAJO');
        //dd($quejasContrato);
        foreach ($completeData as $queja) {
            // Solo actualizamos automáticamente si el campo RECEPCION está vacío
            if (empty($queja->RECEPCION) && $queja->NUMERO_ORDEN) {
                // Buscamos si existe en tbl_quejas_contrato
                $cruces = $quejasContrato->get($queja->NUMERO_ORDEN);

                if ($cruces) {
                    // Validamos contrato y estado
                    // NOTA: en tbl_quejas_contrato puede que el contrato no tenga prefijo o tenga ":". Ajustamos si es necesario.
                    $match = $cruces->first(function($item) use ($queja) {
                        return (str_replace(':', '', $item->CONTRATO) == str_replace(':', '', $queja->CONTRATO))
                            && (trim(strtoupper($item->RESULTADO_CIERRE)) === 'EJECUTADA');
                    });

                    if ($match) {
                        $queja->RECEPCION = 'GDW';
                        $queja->FECHA_RECEPCION = date('Y-m-d'); // <-- Se asigna la fecha automáticamente
                        $queja->save();
                    }
                }
            }
        }
    }

    // --- NUEVO MÉTODO PARA HISTÓRICO ---
    public function getHistorico(Request $request)
    {
        $query = asignadas_quejas::select("*")->where('estado', 0);

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
            $query->whereBetween(DB::raw('STR_TO_DATE(FECHA_ASIGNACION, "%d/%m/%Y")'), [$fechaInicio, $fechaFin])
                ->orWhereBetween('FECHA_ASIGNACION', [$fechaInicio, $fechaFin]);
        }

        $datosHistorico = $query->get();

        return response()->json([
            'success' => true,
            'data' => $datosHistorico
        ]);
    }

    // --- NUEVO MÉTODO PARA EXPORTAR A GDW ---
    public function exportarGDW(Request $request)
    {
        $request->validate([
            'fecha_exportacion' => 'required|date'
        ]);

        $fechaInput = $request->input('fecha_exportacion');
        $fechaDmY = Carbon::parse($fechaInput)->format('d/m/Y');

        $query = asignadas_quejas::where('estado', 1)
            ->whereNotNull('ASIGNADO')
            ->where('ASIGNADO', '!=', '')
            ->where(function($q) use ($fechaInput, $fechaDmY) {
                $q->where('FECHA_ASIGNACION', 'LIKE', $fechaInput . '%')
                    ->orWhere('FECHA_ASIGNACION', 'LIKE', $fechaDmY . '%');
            });

        $datosGDW = $query->get();

        // Verificamos que haya datos antes de generar los archivos
        if ($datosGDW->isEmpty()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontraron registros para exportar en esta fecha.'
            ]);
        }

        // Delegamos TODA la lógica de creación de archivos al Servicio
        $resultadoArchivos = $this->PQRSLectorHTML->CrearArchivos($datosGDW);

        // Agregamos la cantidad encontrada para mostrarla en la alerta del Frontend
        $resultadoArchivos['cantidad_encontrada'] = $datosGDW->count();

        return response()->json($resultadoArchivos);
    }
}
