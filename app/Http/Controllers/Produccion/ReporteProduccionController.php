<?php

namespace App\Http\Controllers\Produccion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Produccion\ActualizarParametrosPreciosRequest;
use App\Http\Requests\Produccion\GuardarProduccionRequest;
use App\Http\Requests\Produccion\InspeccionIndustrialRequest;
use App\Http\Requests\Produccion\InsertarMetasRequest;
use App\Http\Requests\Produccion\ParametrosPreciosRequest;
use App\Models\Nomina\TblParametroPrecios;
use App\Services\Produccion\MetasYProyeccionService;
use App\Services\Produccion\ParametrosPreciosService;
use App\Services\Produccion\ReporteConsolidadoService;
use App\Services\Produccion\ReporteMensualService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReporteProduccionController extends Controller
{
    public function __construct(
        private ReporteMensualService $mensual,
        private ReporteConsolidadoService $consolidado,
        private MetasYProyeccionService $metas,
        private ParametrosPreciosService $precios
    ) {}

    public function diario()
    {
        return view('reporteProduccion.diario', ['currentYear' => date('Y') + 1]);
    }

    /* Hay una ruta por mes —showEnero, showFebrero…— porque así las tiene la
       vista. Todas llevan al mismo sitio con el número del mes. */
    public function showEnero(Request $request)      { return $this->getConsult($request, 1); }
    public function showFebrero(Request $request)    { return $this->getConsult($request, 2); }
    public function showMarzo(Request $request)      { return $this->getConsult($request, 3); }
    public function showAbril(Request $request)      { return $this->getConsult($request, 4); }
    public function showMayo(Request $request)       { return $this->getConsult($request, 5); }
    public function showJunio(Request $request)      { return $this->getConsult($request, 6); }
    public function showJulio(Request $request)      { return $this->getConsult($request, 7); }
    public function showAgosto(Request $request)     { return $this->getConsult($request, 8); }
    public function showSeptiembre(Request $request) { return $this->getConsult($request, 9); }
    public function showOctubre(Request $request)    { return $this->getConsult($request, 10); }
    public function showNoviembre(Request $request)  { return $this->getConsult($request, 11); }
    public function showDiciembre(Request $request)  { return $this->getConsult($request, 12); }

    public function getConsult(Request $request, $mes): JsonResponse
    {
        return response()->json($this->mensual->generar($request->query('anio'), (int) $mes));
    }

    public function guardarProduccion(GuardarProduccionRequest $request)
    {
        echo $this->metas->proyectar($request->input('fechaFila'), $request->input('nuevaCant')) ? 1 : 2;
    }

    public function inspeccionIndustrial(InspeccionIndustrialRequest $request)
    {
        echo $this->metas->inspeccionIndustrial(
            $request->input('fechaFila'), $request->input('valor'), $request->input('totalFinal')
        ) ? 1 : 2;
    }

    public function fechasProduccion()
    {
        $fechaPrecios = TblParametroPrecios::orderBy('id', 'desc')->get();

        return view('reporteProduccion.registrarFechasNomina', compact('fechaPrecios'));
    }

    public function reporteConsolidado()
    {
        // La vista arma el selector con la clave del mes, no con su número.
        $meses = [];

        foreach (ReporteConsolidadoService::MESES as $indice => $nombre) {
            $meses['-' . str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT)] = $nombre;
        }

        return view('reporteProduccion.reporteConsolidado', [
            'meses'       => $meses,
            'currentYear' => date('Y') + 1,
        ]);
    }

    public function generarReporteConsolidado(Request $request): JsonResponse
    {
        return response()->json($this->consolidado->delAno($request->input('anio')));
    }

    public function insertarMetas(InsertarMetasRequest $request)
    {
        echo $this->metas->guardarMetas(
            $request->input('anioMes'), $request->input('metagyc'), $request->input('metagdo')
        ) ? 1 : 2;
    }

    public function generarReportePorMes(Request $request): JsonResponse
    {
        return response()->json($this->consolidado->porZonas($request->input('data')));
    }

    public function guardarFechasParametros(ParametrosPreciosRequest $request): JsonResponse
    {
        return response()->json($this->precios->crear($request->all()));
    }

    public function actualizarFechasParametros(ActualizarParametrosPreciosRequest $request): JsonResponse
    {
        return response()->json($this->precios->actualizar($request->input('id'), $request->all()));
    }
}
