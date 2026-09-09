<?php

namespace App\Http\Controllers\Produccion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Produccion\ActualizarDetalleDiarioRequest;
use App\Http\Requests\Produccion\ContarDoblesSabadoRequest;
use App\Http\Requests\Produccion\DatosDetallesRequest;
use App\Http\Requests\Produccion\DoblesInspectorRequest;
use App\Http\Requests\Produccion\InsertarContratoRequest;
use App\Models\Produccion\TblProduccionCorte;
use App\Models\Produccion\TblProduccionHistorico;
use App\Models\TblInspCali;
use App\Models\Zonificacion\TblLocalidadesMunicipio;
use App\Services\Produccion\AjustesDeDoblesService;
use App\Services\Produccion\ContratoDiarioService;
use App\Services\Produccion\ContratosDelCorteService;
use App\Services\Produccion\DetalleDelCorteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProduccionController extends Controller
{
    public function __construct(
        private ContratosDelCorteService $contratos,
        private DetalleDelCorteService $detalle,
        private ContratoDiarioService $diario,
        private AjustesDeDoblesService $ajustes
    ) {}

    public function index(Request $request)
    {
        $cortes = TblProduccionCorte::all();
        $corte = $request->id
            ? TblProduccionCorte::find($request->id)
            : $this->contratos->vigente();

        if ($corte === null) {
            return view('produccion.index', [
                'produccionInspector' => 'produccionInspector',
                'contratosCategoria'  => 'contratosCategoria',
                'conteoContratosPorZona' => ' conteoContratosPorZona',
                'corte'   => null,
                'warning' => 'No hay corte activo',
                'cortes'  => $cortes,
                'arrayInspectores' => [],
            ]);
        }

        session(['corte_actual_id' => $corte->id]);

        $inspectores = $this->contratos->inspectoresConProduccion($corte);
        $produccionInspector = $this->contratos->produccionPorInspector($corte, $inspectores);

        $warning = match (true) {
            $this->contratos->consulta($corte)->doesntExist() => 'No hay contratos en el corte activo',
            $inspectores->isEmpty()                           => 'No hay inspectores activos',
            default                                           => null,
        };

        if ($warning !== null) {
            return view('produccion.index', compact('produccionInspector', 'corte', 'warning', 'cortes', 'inspectores'));
        }

        $municipiosNoEncontrados = $this->contratos->municipiosSinLocalidad($corte);

        return view('produccion.index', compact(
            'produccionInspector', 'corte', 'warning', 'municipiosNoEncontrados', 'cortes', 'inspectores'
        ));
    }

    public function getCorteData(Request $request): JsonResponse
    {
        $corte = TblProduccionCorte::find($request->id);

        if (! $corte) {
            return response()->json(['error' => 'Corte no encontrado'], 404);
        }

        $inspectores = $request->inspector_cc
            ? TblInspCali::whereIn('cedula', $request->inspector_cc)->get()
            : $this->contratos->inspectoresConProduccion($corte);

        return response()->json([
            'produccionInspector' => $this->contratos->produccionPorInspector($corte, $inspectores),
            'nombreCorte'         => $this->contratos->etiqueta($corte),
        ]);
    }

    public function getCorteTotalData(Request $request): JsonResponse
    {
        if (! $request->cortes) {
            return response()->json(['error' => 'Corte no encontrado'], 404);
        }

        $resultados = TblProduccionCorte::whereIn('id', $request->cortes)->get()
            ->map(fn (TblProduccionCorte $corte) => [
                'id'             => $corte->id,
                'nombreCorte'    => $this->contratos->etiqueta($corte),
                'totalContratos' => $this->contratos->total($corte),
            ]);

        return response()->json($resultados);
    }

    public function detallesCorte($id)
    {
        session(['id_corte' => $id]);

        return $this->detalles();
    }

    public function detalles()
    {
        $municipios = TblLocalidadesMunicipio::all();

        if (! session('id_corte')) {
            $corte = $this->contratos->vigente();

            return view('produccion.detalles', compact('municipios', 'corte'));
        }

        $id_corte = session('id_corte');
        $corte = TblProduccionCorte::find($id_corte);

        if (! $corte) {
            return redirect()->back()->with('error', 'No se encontró el corte seleccionado.');
        }

        session(['fecha_inicio' => $corte->fecha_inicio]);

        return view('produccion.detalles', compact('municipios', 'corte', 'id_corte'));
    }

    public function datosDetalles(DatosDetallesRequest $request): JsonResponse
    {
        $corte = $this->corteDelDetalle($request);

        if ($corte === null) {
            return response()->json(['error' => 'No hay corte activo']);
        }

        $datos = $this->detalle->generar($corte);

        return response()->json($datos ?? ['error' => 'No hay corte activo']);
    }

    /**
     * El corte que toca mostrar en el detalle.
     *
     * Puede venir de la sesión —al entrar desde el listado de cortes—, del
     * parámetro de la petición, o ser el corte vigente. La sesión se limpia en
     * cuanto se usa, para que al recargar la pantalla vuelva a mandar el corte
     * vigente y no se quede clavada en el que se abrió una vez.
     */
    private function corteDelDetalle(DatosDetallesRequest $request): ?TblProduccionCorte
    {
        $id = session('id_corte') ?? $request->idCorteDetalles;

        if (! $id) {
            $corte = $this->contratos->vigente();
            session()->put('corteEnviar', $corte);

            return $corte;
        }

        $corte = TblProduccionCorte::find($id);

        if ($corte && TblProduccionHistorico::where('id_corte', $corte->id)->exists()) {
            session()->forget('id_corte');
            session()->put('corteEnviar', $corte);
        }

        return $corte;
    }

    public function detallesDiario($fecha, $inspector): JsonResponse
    {
        $historico = $this->historicoDelCorte();
        $dobles = $this->diario->estadoDeDobles($historico, $fecha, $inspector);

        return response()->json([
            $this->diario->contratosDelDia($fecha, $inspector),
            false,
            $dobles['festivoExcluido'],
            $dobles['sabadosManuales'],
            $dobles['totalSabado'],
            $this->diario->contadoresPorPrioridad($fecha, $inspector),
        ]);
    }

    public function ActualizarDetallesDiario(ActualizarDetalleDiarioRequest $request, $id): JsonResponse
    {
        $datos = $request->payload;

        try {
            if (! $this->diario->actualizarCampo($id, $datos['prop'], $datos['newValue'])) {
                return response()->json(['message' => 'Campo vacio']);
            }
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al actualizar el contrato']);
        }

        return response()->json(['message' => 'OK']);
    }

    public function eliminarDetallesDiario($id): JsonResponse
    {
        $this->diario->alternarEstado($id);

        return response()->json(['message' => 'OK']);
    }

    public function diseñoEspecial($id): JsonResponse
    {
        $especial = $this->diario->alternarDisenoEspecial($id);

        if ($especial === null) {
            return response()->json(['success' => false, 'message' => 'Contrato no encontrado']);
        }

        return response()->json(['success' => true, 'diseño_especial' => $especial]);
    }

    public function insertarContrato(InsertarContratoRequest $request): JsonResponse
    {
        try {
            $error = $this->diario->insertar($request->data, Auth::user()->name);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al insertar el contrato']);
        }

        return $error === null
            ? response()->json(['ok' => 'Insertado correctamente.'])
            : response()->json(['error' => $error]);
    }

    public function consultarBitacora($fecha, $ccOperario)
    {
        return $this->diario->bitacoraDe($fecha, $ccOperario);
    }

    /* --------------------- Ajustes manuales de los dobles -------------------- */

    public function guardarNoDobles(DoblesInspectorRequest $request): JsonResponse
    {
        return $this->ajustar($request, fn ($historico, $cedula, $fecha) => $this->ajustes->excluirDia($historico, $cedula, $fecha));
    }

    public function contarDobles(DoblesInspectorRequest $request): JsonResponse
    {
        return $this->ajustar($request, fn ($historico, $cedula, $fecha) => $this->ajustes->incluirDia($historico, $cedula, $fecha));
    }

    public function storeNotDoublesHolidays(DoblesInspectorRequest $request): JsonResponse
    {
        return $this->ajustar($request, fn ($historico, $cedula, $fecha) => $this->ajustes->excluirFestivo($historico, $cedula, $fecha));
    }

    public function countDoublesHolidays(DoblesInspectorRequest $request): JsonResponse
    {
        return $this->ajustar($request, fn ($historico, $cedula, $fecha) => $this->ajustes->incluirFestivo($historico, $cedula, $fecha));
    }

    public function countDoublesSaturday(ContarDoblesSabadoRequest $request): JsonResponse
    {
        $contados = $request->input('diasContados');

        return $this->ajustar($request, fn ($historico, $cedula, $fecha) => $this->ajustes->anadirSabado($historico, $cedula, $fecha, $contados));
    }

    public function noContarDoblesSaturday(DoblesInspectorRequest $request): JsonResponse
    {
        return $this->ajustar($request, fn ($historico, $cedula, $fecha) => $this->ajustes->quitarSabado($historico, $cedula, $fecha));
    }

    /**
     * Aplica un ajuste sobre el histórico del corte abierto.
     *
     * Los seis ajustes se diferencian solo en la operación, así que comparten
     * el mismo alrededor: sin corte en la sesión no hay histórico que tocar y
     * se responde sin hacer nada, en vez de reventar como antes.
     */
    private function ajustar(Request $request, callable $operacion): JsonResponse
    {
        $historico = $this->historicoDelCorte();

        if ($historico === null) {
            return response()->json(['success' => false, 'error' => 'No hay corte abierto'], 409);
        }

        $operacion($historico, $request->input('ccInspector'), $request->input('fecha'));

        return response()->json(['success' => true]);
    }

    /** El histórico del corte que la pantalla tiene abierto. */
    private function historicoDelCorte(): ?TblProduccionHistorico
    {
        $corte = session('corteEnviar');

        return $corte === null
            ? null
            : TblProduccionHistorico::where('id_corte', $corte['id'])->first();
    }
}
