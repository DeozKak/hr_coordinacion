<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Programacion\tbl_programacion_base;
use App\Models\Programacion\tbl_programacion_contrato;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Zonificacion\AsignacionTecnicoLocalidad;
use Illuminate\Support\Facades\DB;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     **/
    public function index(Request $request)
    {
        // ... (El Diccionario y la función limpiadora se mantienen igual) ...
        $mapeo_municipios = [
            'SANTIAGO DE CALI'      => 'CALI',
            'CORREGIMIENTO HOLGUIN' => 'LA VICTORIA',
            'CORREGIMIENTO PAVAS'   => 'LA CUMBRE',
        ];

        $limpiarMunicipio = function($nombre_original) use ($mapeo_municipios) {
            // 1. Limpiamos espacios y pasamos a mayúsculas
            $nombre = strtoupper(trim($nombre_original));

            // 2. PRIMERO: Si tiene paréntesis, extraemos lo de adentro
            if (preg_match('/\(([^)]+)\)/', $nombre, $matches)) {
                $nombre = strtoupper(trim($matches[1]));
            }

            // 3. SEGUNDO: Verificamos si el resultado exacto está en el diccionario
            if (array_key_exists($nombre, $mapeo_municipios)) {
                return $mapeo_municipios[$nombre];
            }

            // 4. REGLA AGRESIVA: Si aún contiene la palabra, lo forzamos (útil para errores de tipeo)
            if (str_contains($nombre, 'SANTIAGO DE CALI') || str_contains($nombre, 'SANTIAGO DE CALÍ')) {
                return 'CALI';
            }

            // 5. Si no cumple nada, devolvemos el nombre limpio
            return $nombre;
        };

        $agrupacion = $request->input('agrupacion', 'tipo_trabajo');

        if ($agrupacion == 'meses') {
            $sentencia_agrupacion = "CASE WHEN MESES < 55 THEN '-55' WHEN MESES = 55 THEN '55' WHEN MESES = 56 THEN '56' WHEN MESES = 57 THEN '57' WHEN MESES = 58 THEN '58' WHEN MESES = 59 THEN '59' WHEN MESES = 60 THEN '60' WHEN MESES > 60 THEN '60+' ELSE 'Sin Mes' END";
            $select_criterio = "$sentencia_agrupacion as criterio";
            $group_criterio = $sentencia_agrupacion;
            $titulo_columna = "Meses de Vencimiento";
        } else {
            $select_criterio = "ID_TIPO_TRABAJO as criterio";
            $group_criterio = "ID_TIPO_TRABAJO";
            $titulo_columna = "ID Tipo de Trabajo";
        }

        // 3. Resumen General
        $resumen_asignaciones = tbl_programacion_base::selectRaw("$select_criterio, COUNT(*) as total_asignados, SUM(CASE WHEN ESTADO_RECEPCION = '1' THEN 1 ELSE 0 END) as total_ejecutados")
            ->groupByRaw($group_criterio)->get();

        // 4. Matriz de Pendientes
        $datos_matriz = tbl_programacion_base::where('ESTADO_RECEPCION', '!=', '1')
            ->selectRaw("DESC_LOCALIDAD, $select_criterio, COUNT(*) as cantidad")
            ->groupBy('DESC_LOCALIDAD')->groupByRaw($group_criterio)->get();

        foreach ($datos_matriz as $item) $item->MUNICIPIO_MADRE = $limpiarMunicipio($item->DESC_LOCALIDAD);

        $resumen_localidades = $datos_matriz->groupBy('MUNICIPIO_MADRE')->map(function ($grupo) {
            return $grupo->groupBy('criterio')->map(function ($items_criterio) {
                return (object)['criterio' => $items_criterio->first()->criterio, 'cantidad' => $items_criterio->sum('cantidad')];
            })->values();
        });

        $criterios_disponibles = $datos_matriz->pluck('criterio')->unique();
        if ($agrupacion == 'meses') {
            $criterios_disponibles = $criterios_disponibles->sortBy(function($val) {
                if ($val === '-55') return 0; if ($val === '60+') return 100; if ($val === 'Sin Mes') return 101; return (int) $val;
            })->values();
        } else {
            $criterios_disponibles = $criterios_disponibles->sort()->values();
        }

        // =========================================================================
        // 5. NUEVO PASO 5: Lista de Técnicos DESDE LA NUEVA TABLA
        // =========================================================================
        $tecnicos_brutos = AsignacionTecnicoLocalidad::leftJoin('tbl_insp_cali', 'tbl_asignacion_tecnicos_localidad.id_tecnico', '=', 'tbl_insp_cali.id')
            ->select(
                'tbl_asignacion_tecnicos_localidad.localidad AS DESC_LOCALIDAD',
                'tbl_asignacion_tecnicos_localidad.id_tecnico AS ID_TECNICO',
                DB::raw("CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS NOMBRE_COMPLETO")
            )
            ->get();

        // Aplicamos la misma limpieza para agrupar bonito en la vista
        foreach ($tecnicos_brutos as $tec) {
            $tec->MUNICIPIO_MADRE = $limpiarMunicipio($tec->DESC_LOCALIDAD);
        }

        $tecnicos_por_localidad = $tecnicos_brutos->groupBy('MUNICIPIO_MADRE')->map(function ($grupo) {
            return $grupo->unique('ID_TECNICO')->values();
        });

        // OBTENER ASIGNACIONES ACTUALES (Para saber quién está ocupado y dónde)
        $asignaciones_totales = AsignacionTecnicoLocalidad::all()->pluck('localidad', 'id_tecnico');

        // OBTENER TODOS LOS TÉCNICOS ACTIVOS
        $todos_los_tecnicos = DB::table('tbl_insp_cali')
            ->select('id', DB::raw("CONCAT(apellidos, ' ', nombres) AS NOMBRE_COMPLETO"))
            ->whereNotNull('id')
            ->where('id', '!=', '100')
            ->orderBy('apellidos')
            ->get()
            ->map(function($t) use ($asignaciones_totales) {
                // Le pegamos a cada técnico la localidad donde ya está asignado (si existe)
                $t->asignado_en = $asignaciones_totales[$t->id] ?? null;
                return $t;
            });

        // =========================================================================
        // 6. NUEVO: Programaciones del Día Actual
        // =========================================================================
        $hoy = Carbon::now()->toDateString(); // Extrae la fecha de hoy en formato 'YYYY-MM-DD'

        $programaciones_hoy = tbl_programacion_contrato::whereDate('FECHA_AGENDAMIENTO', $hoy)
            ->selectRaw("
                TIPO_TRABAJO,
                COUNT(*) as total_programadas,
                SUM(CASE WHEN EJECUTADA = '1' THEN 1 ELSE 0 END) as total_ejecutadas
            ")
            ->groupBy('TIPO_TRABAJO')
            ->get();

        return view('home', compact(
            'resumen_asignaciones', 'resumen_localidades', 'criterios_disponibles', 'titulo_columna',
            'agrupacion', 'tecnicos_por_localidad', 'programaciones_hoy',
            'todos_los_tecnicos' // <-- Pasamos los técnicos al modal
        ));
    }

    public function guardarAsignacion(Request $request)
    {
        $request->validate([
            'localidad' => 'required|string',
            'tecnicos'  => 'nullable|array' // Permitimos que sea nulo por si borran todos
        ]);

        $localidad = strtoupper(trim($request->localidad));

        DB::transaction(function () use ($localidad, $request) {
            // 1. Borramos la asignación previa de esta localidad para "limpiar"
            AsignacionTecnicoLocalidad::where('localidad', $localidad)->delete();

            // 2. Guardamos los técnicos seleccionados
            if ($request->has('tecnicos')) {
                foreach ($request->tecnicos as $id_tecnico) {
                    AsignacionTecnicoLocalidad::create([
                        'localidad'  => $localidad,
                        'id_tecnico' => $id_tecnico
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', "Asignación de $localidad actualizada con éxito.");
    }

}
