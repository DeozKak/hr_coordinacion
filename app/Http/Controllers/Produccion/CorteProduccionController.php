<?php

namespace App\Http\Controllers\Produccion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Produccion\ActualizarCorteRequest;
use App\Http\Requests\Produccion\CambiarEstadoRequest;
use App\Models\Bitacoras\TblBitacorasCausal;
use App\Models\Produccion\TblProduccionCorte;
use App\Models\Produccion\TblProduccionZona;
use App\Models\Zonificacion\TblLocalidadesSede;
use App\Services\Produccion\CatalogoProduccionService;
use App\Services\Produccion\CorteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CorteProduccionController extends Controller
{
    public function __construct(
        private CorteService $cortes
    ) {}

    public function index()
    {
        return view('corte.index', [
            'cortes'   => TblProduccionCorte::all(),
            'sedes'    => TblLocalidadesSede::all(),
            'zonas'    => TblProduccionZona::all(),
            'causales' => TblBitacorasCausal::all(),
        ]);
    }

    /* ------------------------------- Cortes ------------------------------- */

    public function storeCorte(Request $request): JsonResponse
    {
        return $this->responder(fn () => $this->cortes->crear($request->all()));
    }

    public function editCorte($id): JsonResponse
    {
        return response()->json([TblProduccionCorte::find($id)]);
    }

    public function updateCorte(ActualizarCorteRequest $request, $id): JsonResponse
    {
        return $this->responder(fn () => $this->cortes->actualizar($id, $request->all()));
    }

    /* ------------------------------- Sedes -------------------------------- */

    public function storeSede(Request $request): JsonResponse
    {
        return $this->responder(fn () => $this->catalogo('sede')->crear($request->all()));
    }

    public function editSede($id): JsonResponse
    {
        return response()->json([$this->catalogo('sede')->buscar($id)]);
    }

    public function updateSede(Request $request, $id): JsonResponse
    {
        return $this->responder(fn () => $this->catalogo('sede')->actualizar($id, $request->all()));
    }

    /* ------------------------------- Zonas -------------------------------- */

    public function storeZona(Request $request): JsonResponse
    {
        return $this->responder(fn () => $this->catalogo('zona')->crear($request->all()));
    }

    public function editZona($id): JsonResponse
    {
        return response()->json([$this->catalogo('zona')->buscar($id)]);
    }

    public function updateZona(Request $request, $id): JsonResponse
    {
        return $this->responder(fn () => $this->catalogo('zona')->actualizar($id, $request->all()));
    }

    /* ------------------------------ Causales ------------------------------ */

    public function storeCausal(Request $request): JsonResponse
    {
        return $this->responder(fn () => $this->catalogo('causal')->crear($request->all()));
    }

    public function editCausal($id): JsonResponse
    {
        return response()->json([$this->catalogo('causal')->buscar($id)]);
    }

    public function updateCausal(Request $request, $id): JsonResponse
    {
        return $this->responder(fn () => $this->catalogo('causal')->actualizar($id, $request->all()));
    }

    /* --------------------------- Activar/desactivar ------------------------ */

    public function changeStatusSede(CambiarEstadoRequest $request): JsonResponse
    {
        return $this->responder(fn () => $this->catalogo('sede')->alternarEstado(intval($request->input('id'))));
    }

    public function changeStatusZona(CambiarEstadoRequest $request): JsonResponse
    {
        return $this->responder(fn () => $this->catalogo('zona')->alternarEstado(intval($request->input('id'))));
    }

    public function changeStatusCausal(CambiarEstadoRequest $request): JsonResponse
    {
        return $this->responder(fn () => $this->catalogo('causal')->alternarEstado(intval($request->input('id'))));
    }

    private function catalogo(string $cual): CatalogoProduccionService
    {
        return CatalogoProduccionService::para($cual);
    }

    /**
     * Traduce lo que devuelve el servicio a una respuesta JSON.
     *
     * El servicio dice qué pasó y con qué código; el controlador solo lo
     * envuelve. Un fallo inesperado se registra y sale como 500, que es lo que
     * hacían los quince bloques try/catch que había aquí.
     */
    private function responder(callable $operacion): JsonResponse
    {
        try {
            $resultado = $operacion();
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json($resultado['datos'], $resultado['estado'] ?? 200);
    }
}
