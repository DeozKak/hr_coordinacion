<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Home\CargueEstadisticasAsignacionService;
use App\Services\Home\EstadisticasProgramadasService;
use App\Services\Home\FuerzaTrabajoService;
use App\Services\Home\PendientesBaseService;
use App\Services\Home\ReporteOperativoService;
use Illuminate\Http\Request;
use Carbon\Carbon;


class HomeController extends Controller
{
    public function __construct(
        private FuerzaTrabajoService $fuerzaTrabajo,
        private ReporteOperativoService $reporteOperativo,
        private EstadisticasProgramadasService $estadisticasProgramadas,
        private PendientesBaseService $pendientesBase
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
        $base        = $this->pendientesBase->generar();

        return view('home', [
            'tecnicos_por_localidad'  => $this->fuerzaTrabajo->tecnicosPorLocalidad(),
            'todos_los_tecnicos'      => $this->fuerzaTrabajo->todosLosTecnicos(),
            'fechaReporte'            => $fechaReporte,
            'localidadSeleccionada'   => $localidadSeleccionada,
            'localidadesDisponibles'  => $reporte['localidadesDisponibles'],
            'metricas'                => $reporte['metricas'],
            'acumuladoDesde'          => $reporte['acumuladoDesde'],
            'mesesData'               => $reporte['mesesData'],
            'detalles'                => $reporte['detalles'],
            'estadisticasProgramadas' => $programadas['estadisticas'],
            'totalesProg'             => $programadas['totales'],
            'detallesProgramaciones'  => $programadas['detalles'],
            'baseTipos'               => $base['tipos'],
            'baseMeses'               => $base['meses'],
            'baseTotalTipos'          => $base['totalTipos'],
            'baseTotalMeses'          => $base['totalMeses'],
            'baseTotalTabla'          => $base['totalTabla'],
        ]);
    }

    public function guardarAsignacion(Request $request)
    {
        $request->validate([
            'localidad' => 'required|string',
            'tecnicos'  => 'nullable|array' // Permitimos que sea nulo por si borran todos
        ]);

        $localidad = strtoupper(trim($request->localidad));

        $resultado = $this->fuerzaTrabajo->asignar($localidad, $request->input('tecnicos', []));

        $mensaje = "Asignación de $localidad actualizada con éxito.";

        // Se avisa de dónde salieron los técnicos que se trajeron de otra localidad.
        // Con pocos se nombran; con muchos solo se resume, porque el aviso es un toast.
        $movidos = collect($resultado['movidos']);

        if ($movidos->isNotEmpty()) {
            $mensaje .= $movidos->count() <= 3
                ? ' Se trasladaron: ' . $movidos->map(fn ($origen, $nombre) => "$nombre (venía de $origen)")->implode('; ') . '.'
                : ' Se trasladaron ' . $movidos->count() . ' técnicos desde: ' . $movidos->values()->unique()->implode(', ') . '.';
        }

        return redirect()->back()->with('success', $mensaje);
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
