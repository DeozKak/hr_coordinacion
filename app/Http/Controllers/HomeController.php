<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Home\CargueEstadisticasAsignacionService;
use App\Services\Home\EstadisticasProgramadasService;
use App\Services\Home\FuerzaTrabajoService;
use App\Services\Home\ReporteOperativoService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Zonificacion\AsignacionTecnicoLocalidad;
use Illuminate\Support\Facades\DB;


class HomeController extends Controller
{
    public function __construct(
        private FuerzaTrabajoService $fuerzaTrabajo,
        private ReporteOperativoService $reporteOperativo,
        private EstadisticasProgramadasService $estadisticasProgramadas
    ) {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     **/
    public function index(Request $request)
    {
        $fechaReporte          = (string) $request->input('fecha_reporte', Carbon::today()->format('Y-m-d'));
        $localidadSeleccionada = (string) $request->input('localidad_reporte', 'TODAS');

        $reporte     = $this->reporteOperativo->generar($fechaReporte, $localidadSeleccionada);
        $programadas = $this->estadisticasProgramadas->generar($fechaReporte, $localidadSeleccionada);

        return view('home', [
            'tecnicos_por_localidad'  => $this->fuerzaTrabajo->tecnicosPorLocalidad(),
            'todos_los_tecnicos'      => $this->fuerzaTrabajo->todosLosTecnicos(),
            'fechaReporte'            => $fechaReporte,
            'localidadSeleccionada'   => $localidadSeleccionada,
            'localidadesDisponibles'  => $reporte['localidadesDisponibles'],
            'metricas'                => $reporte['metricas'],
            'mesesData'               => $reporte['mesesData'],
            'detalles'                => $reporte['detalles'],
            'estadisticasProgramadas' => $programadas['estadisticas'],
            'totalesProg'             => $programadas['totales'],
            'detallesProgramaciones'  => $programadas['detalles'],
        ]);
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


    public function insercion_estadisticas_asignacion(Request $request, CargueEstadisticasAsignacionService $cargue)
    {
        $request->validate([
            'archivo_asignacion' => 'required|file|mimes:xls,xlsx,csv',
            'archivo_cerradas'   => 'required|file|mimes:xls,xlsx,csv',
        ], [
            'archivo_asignacion.required' => 'El archivo de Asignación (OT Abiertas) es obligatorio.',
            'archivo_asignacion.mimes'    => 'El archivo de Asignación debe ser de formato Excel o CSV.',
            'archivo_cerradas.required'   => 'El archivo de OT Cerradas es obligatorio.',
            'archivo_cerradas.mimes'      => 'El archivo de OT Cerradas debe ser de formato Excel o CSV.',
        ]);

        $cargue->procesar(
            $request->file('archivo_asignacion'),
            $request->file('archivo_cerradas')
        );

        return redirect()->back()->with('success', 'Archivos procesados y sincronizados correctamente.');
    }
}
