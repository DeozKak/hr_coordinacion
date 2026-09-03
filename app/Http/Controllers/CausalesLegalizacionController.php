<?php

namespace App\Http\Controllers;

use App\Models\CausalLegalizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mantenimiento de las causales que cuentan como legalización.
 *
 * La lista la maneja coordinación porque GDO añade causales cada tanto y quien
 * sabe cuáles legalizan es quien trabaja con ellas, no el programador.
 */
class CausalesLegalizacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('causales.index', [
            'causales' => $this->listado(),
            'sueltas'  => $this->causalesSinRegistrar(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'causal' => ['required', 'string', 'max:255'],
        ], [
            'causal.required' => 'Escribe la causal.',
            'causal.max'      => 'La causal no puede pasar de 255 caracteres.',
        ]);

        $clave = CausalLegalizacion::normalizar($datos['causal']);

        if (CausalLegalizacion::where('clave', $clave)->exists()) {
            return response()->json(['message' => 'Esa causal ya está en la lista.'], 422);
        }

        CausalLegalizacion::create([
            'causal'     => trim($datos['causal']),
            'clave'      => $clave,
            'activa'     => true,
            'creado_por' => auth()->id(),
        ]);

        return $this->respuesta('Causal añadida.');
    }

    /**
     * Enciende o apaga una causal.
     *
     * Se apaga en vez de borrarse cuando deja de valer: así queda constancia de
     * que existió y se puede volver a encender sin escribirla de nuevo.
     */
    public function alternar(CausalLegalizacion $causal)
    {
        $causal->update(['activa' => ! $causal->activa]);

        return $this->respuesta($causal->activa ? 'Causal activada.' : 'Causal desactivada.');
    }

    public function destroy(CausalLegalizacion $causal)
    {
        $causal->delete();

        return $this->respuesta('Causal eliminada.');
    }

    /** Respuesta común: el mensaje y la lista ya rehecha. */
    private function respuesta(string $mensaje)
    {
        return response()->json([
            'mensaje'  => $mensaje,
            'causales' => $this->listado(),
            'sueltas'  => $this->causalesSinRegistrar(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listado(): array
    {
        return CausalLegalizacion::query()
            ->orderByDesc('activa')
            ->orderBy('causal')
            ->get()
            ->map(fn (CausalLegalizacion $c) => [
                'id'     => $c->id,
                'causal' => $c->causal,
                'activa' => $c->activa,
                'filas'  => $this->cuantasFilas($c->clave),
            ])
            ->all();
    }

    /**
     * Causales que están en los datos cargados pero no en la lista.
     *
     * Es el aviso que evita que esto se quede viejo en silencio: si GDO manda
     * una causal nueva, aquí se ve —con cuántas órdenes trae— y se decide si
     * legaliza o no, en vez de descubrirlo cuando los pendientes no cuadran.
     *
     * @return list<array{causal: string, filas: int}>
     */
    private function causalesSinRegistrar(): array
    {
        $conocidas = CausalLegalizacion::pluck('clave')->flip();

        return DB::table('tbl_cerradas')
            ->select('DESCCAUSAL', DB::raw('COUNT(*) AS filas'))
            ->whereNotNull('DESCCAUSAL')
            ->where('DESCCAUSAL', '<>', '')
            ->groupBy('DESCCAUSAL')
            ->get()
            ->reject(fn ($f) => $conocidas->has(CausalLegalizacion::normalizar($f->DESCCAUSAL)))
            ->sortByDesc('filas')
            ->map(fn ($f) => ['causal' => $f->DESCCAUSAL, 'filas' => (int) $f->filas])
            ->values()
            ->all();
    }

    /** Cuántas órdenes cargadas traen esa causal. */
    private function cuantasFilas(string $clave): int
    {
        static $conteo = null;

        if ($conteo === null) {
            $conteo = DB::table('tbl_cerradas')
                ->select('DESCCAUSAL', DB::raw('COUNT(*) AS filas'))
                ->groupBy('DESCCAUSAL')
                ->get()
                ->reduce(function (array $acc, $f) {
                    $k = CausalLegalizacion::normalizar($f->DESCCAUSAL);
                    $acc[$k] = ($acc[$k] ?? 0) + (int) $f->filas;
                    return $acc;
                }, []);
        }

        return $conteo[$clave] ?? 0;
    }
}
