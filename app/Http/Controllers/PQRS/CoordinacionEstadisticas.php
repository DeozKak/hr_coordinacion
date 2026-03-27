<?php

namespace App\Http\Controllers\PQRS;

use App\Http\Controllers\Controller;
use App\Models\asignadas_quejas;

use App\Models\tbl_insp_cali;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CoordinacionEstadisticas extends Controller
{
    public function index(Request $request)
    {
        // 1. CAPTURAR LOS FILTROS
        $estadoFiltro = $request->input('estado', '1');
        $tipoFecha = $request->input('tipo_fecha', 'asignacion');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $inspectorFiltro = $request->input('inspector'); // <-- NUEVO FILTRO

        // --- OBTENER LISTA DE INSPECTORES PARA EL SELECT ---
        $inspectoresBD = tbl_insp_cali::where('state', 1)->get();
        $listaInspectores = $inspectoresBD->map(function ($i) {
            return "{$i->id}. {$i->apellidos} {$i->nombres}";
        })->toArray();

        // 2. CONSTRUIR LA CONSULTA BASE
        $query = asignadas_quejas::query();

        // Filtro de Estado
        if ($estadoFiltro === '1') {
            $query->where('estado', 1);
            $fechaInicio = null;
            $fechaFin = null;
        } elseif ($estadoFiltro === '0') {
            $query->where('estado', 0);
        }

        // Filtro de Rango de Fechas
        if (!empty($fechaInicio) && !empty($fechaFin)) {
            if ($tipoFecha === 'asignacion') {
                $query->where(function($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween(DB::raw('STR_TO_DATE(FECHA_ASIGNACION, "%d/%m/%Y")'), [$fechaInicio, $fechaFin])
                        ->orWhereBetween('FECHA_ASIGNACION', [$fechaInicio, $fechaFin]);
                });
            } elseif ($tipoFecha === 'legalizacion') {
                $query->where(function($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween(DB::raw('STR_TO_DATE(FECHA_LEGALIZACION, "%d/%m/%Y")'), [$fechaInicio, $fechaFin])
                        ->orWhereBetween('FECHA_LEGALIZACION', [$fechaInicio, $fechaFin]);
                });
            }
        }

        // --- APLICAR FILTRO DE INSPECTOR ---
        if (!empty($inspectorFiltro)) {
            $query->where('ASIGNADO', $inspectorFiltro);
        }

        // Ejecutamos la consulta con todos los filtros aplicados
        $quejas = $query->get();

        // 4. FILTRO EN MEMORIA: Separamos las que sí tienen técnico
        $quejasAsignadas = $quejas->filter(function ($q) {
            return !empty(trim($q->ASIGNADO));
        });


        // --- GRÁFICA 1: TÉCNICOS TOP 10 (Usa solo asignadas) ---
        $tecnicosTop = $quejasAsignadas->groupBy('ASIGNADO')->map->count()->sortDesc()->take(10);


        // --- GRÁFICA 2: MOTIVOS GLOBALES ---
        // Cuenta cantidad de quejas por cada motivo sin importar el técnico
        $motivosAgrupados = $quejas->filter(function ($q) {
            return !empty($q->MOTIVO_DE_PQR);
        })->groupBy('MOTIVO_DE_PQR')->map->count()->sortDesc();


        // --- GRÁFICA 3: ACCESO VS NO ACCEDE (Global) ---
        $accesoStats = ['Accede' => 0, 'No Accede' => 0, 'Pendiente / Otro' => 0];

        foreach ($quejas as $q) {
            $causal = strtoupper($q->RECEPCION ?? ''); // Mantengo tu lógica usando RECEPCION
            if (strpos($causal, 'NO ACCEDE') !== false) {
                $accesoStats['No Accede']++;
            } elseif (strpos($causal, 'ACCEDE') !== false || strpos($causal, 'EFECTIVA') !== false) {
                $accesoStats['Accede']++;
            } else {
                $accesoStats['Pendiente / Otro']++;
            }
        }


        // --- GRÁFICA 4: A TIEMPO VS VENCIDAS (Global) ---
        $tiemposStats = ['A tiempo' => 0, 'Vencida' => 0];

        foreach ($quejas as $q) {
            if (!isset($q->DIAS_FALTANTES) || $q->DIAS_FALTANTES === '') continue;

            $diasFaltantes = (int) $q->DIAS_FALTANTES;
            if ($diasFaltantes < 0) {
                $tiemposStats['Vencida']++;
            } else {
                $tiemposStats['A tiempo']++;
            }
        }

        // Enviamos todo a la vista, incluyendo el filtro actual para mantenerlo seleccionado
        return view('pqrs.estadisticas', compact('tecnicosTop', 'motivosAgrupados', 'accesoStats', 'tiemposStats', 'estadoFiltro', 'tipoFecha', 'fechaInicio', 'fechaFin', 'listaInspectores', 'inspectorFiltro'));
    }
}
