<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CorteGdo;
use App\Services\Home\CargueEstadisticasAsignacionService;
use App\Services\Home\CorteGdoService;
use App\Services\Home\EstadisticasProgramadasService;
use App\Services\Home\FuerzaTrabajoService;
use App\Services\Home\PendientesBaseService;
use App\Services\Home\ReporteOperativoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        /* El segundo argumento de input() solo entra cuando la clave NO viene.
           Al borrar la fecha y pulsar Filtrar, el formulario sí manda
           `fecha_reporte`, pero vacío: el valor por defecto no se aplicaba y la
           cadena vacía llegaba hasta la consulta, que reventaba.
           `sometimes` deja pasar la carga directa sin parámetros —ahí manda hoy—
           y `required` corta el filtro sin fecha. */
        $validador = Validator::make($request->all(), [
            'fecha_reporte'     => ['sometimes', 'required', 'date_format:Y-m-d'],
            'localidad_reporte' => ['nullable', 'string', 'max:120'],
        ], [
            'fecha_reporte.required'    => 'Selecciona una fecha para filtrar el reporte.',
            'fecha_reporte.date_format' => 'La fecha del filtro no tiene un formato válido.',
            'localidad_reporte.max'     => 'El municipio del filtro no es válido.',
        ]);

        if ($validador->fails()) {
            // Se vuelve al reporte de hoy en vez de rebotar contra el referente,
            // que en un filtro por GET puede ser la propia URL inválida.
            return redirect()->route('home')->with('error', $validador->errors()->first());
        }

        $fechaReporte = $request->filled('fecha_reporte')
            ? (string) $request->input('fecha_reporte')
            : Carbon::today()->format('Y-m-d');

        $localidadSeleccionada = $request->filled('localidad_reporte')
            ? (string) $request->input('localidad_reporte')
            : 'TODAS';

        $reporte = $this->reporteOperativo->generar($fechaReporte, $localidadSeleccionada);
        $base    = $this->pendientesBase->generar();

        /* La tarjeta de programaciones tiene filtro propio, así que no arranca
           con lo que diga el de la cabecera: siempre parte del día de hoy y
           todos los municipios. Desde ahí el usuario la mueve por su cuenta. */
        $hoy = Carbon::today()->format('Y-m-d');
        $programadas = $this->estadisticasProgramadas->generar($hoy, 'TODAS');

        return view('home', [
            'fuerzaLocalidades'       => $this->fuerzaTrabajo->localidadesParaVista(),
            'catalogoTecnicos'        => $this->fuerzaTrabajo->catalogoParaVista(),
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
            'ciudadesProgramaciones'  => $programadas['ciudades'],
            'corteGdo'                => $reporte['corte'],
            'baseTipos'               => $base['tipos'],
            'baseMeses'               => $base['meses'],
            'baseTotalTipos'          => $base['totalTipos'],
            'baseTotalMeses'          => $base['totalMeses'],
            'baseTotalTabla'          => $base['totalTabla'],
        ]);
    }

    /**
     * Reporte operativo del día para el filtro de la cabecera.
     *
     * Mismos datos que pinta index(), pero en JSON, para que cambiar la fecha o
     * el municipio actualice los indicadores, la gráfica y las ventanas de
     * detalle sin recargar la página entera.
     *
     * Se deja fuera lo que no depende de este filtro: la fuerza de trabajo, los
     * pendientes en base y la tarjeta de programaciones, que tiene el suyo.
     */
    public function reporte(Request $request)
    {
        $datos = $request->validate([
            'fecha'     => ['required', 'date_format:Y-m-d'],
            'localidad' => ['nullable', 'string', 'max:120'],
        ], [
            'fecha.required'    => 'Selecciona una fecha para filtrar el reporte.',
            'fecha.date_format' => 'La fecha del filtro no tiene un formato válido.',
            'localidad.max'     => 'El municipio del filtro no es válido.',
        ]);

        $localidad = ($datos['localidad'] ?? '') !== '' ? $datos['localidad'] : 'TODAS';

        $reporte = $this->reporteOperativo->generar($datos['fecha'], $localidad);

        return response()->json([
            'metricas'               => $reporte['metricas'],
            'detalles'               => $reporte['detalles'],
            'mesesData'              => $reporte['mesesData'],
            'localidadesDisponibles' => $reporte['localidadesDisponibles'],
            'acumuladoDesde'         => $reporte['acumuladoDesde'],
            'corte'                  => $reporte['corte'],
            'fecha'                  => $datos['fecha'],
            'localidad'              => $localidad,
        ]);
    }

    /**
     * Estadísticas de programaciones para su propio filtro de fecha y municipio.
     *
     * La tarjeta de programaciones del inicio tiene un filtro aparte del de
     * arriba, así que necesita recalcularse sola. Devuelve lo mismo que ya
     * consume la vista —filas, totales y detalles por tipo— para que la tabla y
     * sus ventanas se refresquen sin recargar la página.
     *
     * Las reglas son las del filtro principal: fecha obligatoria y con formato.
     */
    public function programaciones(Request $request)
    {
        $datos = $request->validate([
            'fecha'  => ['required', 'date_format:Y-m-d'],
            'ciudad' => ['nullable', 'string', 'max:120'],
        ], [
            'fecha.required'    => 'Selecciona una fecha para filtrar las programaciones.',
            'fecha.date_format' => 'La fecha del filtro no tiene un formato válido.',
            'ciudad.max'        => 'El municipio del filtro no es válido.',
        ]);

        $ciudad = ($datos['ciudad'] ?? '') !== '' ? $datos['ciudad'] : 'TODAS';

        $programadas = $this->estadisticasProgramadas->generar($datos['fecha'], $ciudad);

        return response()->json([
            'estadisticas' => $programadas['estadisticas'],
            'totales'      => $programadas['totales'],
            'detalles'     => $programadas['detalles'],
            // La lista cambia con la fecha, así que viaja con la respuesta.
            'ciudades'     => $programadas['ciudades'],
            'fecha'        => $datos['fecha'],
            'ciudad'       => $ciudad,
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

        /* La tarjeta se actualiza sin recargar: se devuelve la fuerza de
           trabajo entera ya recalculada, y el catálogo de técnicos con ella,
           porque al mover a alguien cambian las etiquetas de "actualmente en"
           del propio selector. El redirect se conserva para el envío normal
           del formulario, que es lo que ocurre si el JS no llegó a cargar. */
        if ($request->expectsJson()) {
            return response()->json([
                'mensaje'     => $mensaje,
                'localidades' => $this->fuerzaTrabajo->localidadesParaVista(),
                'tecnicos'    => $this->fuerzaTrabajo->catalogoParaVista(),
            ]);
        }

        return redirect()->back()->with('success', $mensaje);
    }


    /**
     * Define el corte de GDO y devuelve el tablero recalculado.
     *
     * El corte manda sobre tres cifras —lo legalizado en el periodo y los dos
     * acumulados—, así que al guardarlo se responde con las métricas y los
     * detalles ya rehechos y la vista se actualiza sin recargar.
     */
    public function guardarCorte(Request $request, CorteGdoService $corteGdo)
    {
        $datos = $request->validate([
            // Con id se corrige el corte que ya existe; sin id se crea uno nuevo.
            'id'           => ['nullable', 'integer', 'exists:tbl_cortes_gdo,id'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin'    => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
        ], [
            'id.exists'                 => 'El corte que intentas editar ya no existe.',
            'fecha_inicio.required'     => 'Indica la fecha de inicio del corte.',
            'fecha_inicio.date_format'  => 'La fecha de inicio no tiene un formato válido.',
            'fecha_fin.required'        => 'Indica la fecha de fin del corte.',
            'fecha_fin.date_format'     => 'La fecha de fin no tiene un formato válido.',
            'fecha_fin.after_or_equal'  => 'El fin del corte no puede ser anterior a su inicio.',
        ]);

        /* Un corte que se solape con otro dejaría ambiguo cuál es el vigente,
           y la vista tendría que elegir por su cuenta. Se rechaza aquí.
           Al editar se excluye el propio corte: si no, chocaría consigo mismo
           y no habría forma de corregir una fecha. */
        $solapado = CorteGdo::query()
            ->where('fecha_inicio', '<=', $datos['fecha_fin'])
            ->where('fecha_fin', '>=', $datos['fecha_inicio'])
            ->when(! empty($datos['id']), fn ($q) => $q->whereKeyNot($datos['id']))
            ->first();

        if ($solapado) {
            return response()->json([
                'message' => 'Ese periodo se cruza con el corte del '
                    . $solapado->fecha_inicio->format('d/m/Y') . ' al '
                    . $solapado->fecha_fin->format('d/m/Y') . '.',
            ], 422);
        }

        $campos = ['fecha_inicio' => $datos['fecha_inicio'], 'fecha_fin' => $datos['fecha_fin']];

        if (! empty($datos['id'])) {
            CorteGdo::findOrFail($datos['id'])->update($campos);
        } else {
            CorteGdo::create($campos + ['creado_por' => auth()->id()]);
        }

        $reporte = $this->reporteOperativo->generar(
            $request->input('fecha', Carbon::today()->format('Y-m-d')),
            $request->input('localidad', 'TODAS')
        );

        return response()->json([
            'mensaje'  => empty($datos['id']) ? 'Corte de GDO creado.' : 'Corte de GDO actualizado.',
            'corte'    => $reporte['corte'],
            'metricas' => $reporte['metricas'],
            'detalles' => $reporte['detalles'],
        ]);
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
