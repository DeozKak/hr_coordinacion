<?php

namespace App\Http\Controllers\Programacion;

use App\Http\Controllers\Controller;

use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Programacion\tbl_programacion_usuario;
use App\Models\User;
use App\Services\Programacion\CierreProgramacionService;
use App\Services\Programacion\ProgramacionContratoService;
use App\Services\Programacion\ProgramacionUsuarioService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * La programación de trabajo y los contratos que lleva dentro.
 *
 * Abrir una programación, ir metiéndole contratos, corregirlos y cerrarla.
 * Las importaciones de Excel y la pantalla de agendamiento tienen sus propios
 * controladores: aquí sólo vive el ciclo de vida de la programación.
 */
class ProgramacionController extends Controller
{
    public function __construct(
        private ProgramacionContratoService $contratos,
        private ProgramacionUsuarioService $programaciones,
        private CierreProgramacionService $cierre
    ) {
        $this->middleware('auth');
    }


    public function index()
    {
        ['datos' => $datos, 'enCurso' => $temp] = $this->programaciones->listar(Auth::user());

        if ($temp === null) {
            return view('programacion.index', compact('datos'));
        }

        session()->flash('warning', 'Ya tienes una tabla de programación en curso ¿Deseas continuar?');

        return view('programacion.index', compact('datos', 'temp'));
    }


    public function create()
    {
        // Si ya tiene una a medias no se abre otra: se le lleva al listado.
        if ($this->programaciones->enCurso(Auth::user()) !== null) {
            return $this->index();
        }

        try {
            $programacion = $this->programaciones->abrir(Auth::user());
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', 'Ocurrió un error al crear tabla ' . $e->getMessage());

            return redirect()->route('programacion.index');
        }

        $tecnicos = $this->programaciones->tecnicosActivos();
        $user = Auth::user();

        return view('programacion.create', compact('tecnicos', 'user', 'programacion'));
    }


    public function show(Request $request, $id)
    {
        $action = $request->query('action');
        $programacion = tbl_programacion_usuario::find($id);

        if ($action === 'edit') {
            if (! auth()->user()->hasPermissionTo('generar_programacion')) {
                session()->flash('error', 'Acción no autorizada.');

                return redirect()->route('programacion.index');
            }

            try {
                $this->programaciones->reabrir($programacion);
            } catch (\Exception $e) {
                Log::error($e);
                session()->flash('error', 'Ocurrió un error al cargar tabla ' . $e->getMessage());

                return redirect()->route('programacion.index');
            }
        }

        $tabla = $this->programaciones->contratos($id);
        $user = User::find($programacion->id_usuario);
        $tecnicos = $this->programaciones->tecnicosActivos();

        if ($action === 'view') {
            $view = true;

            return view('programacion.create', compact('tecnicos', 'user', 'programacion', 'tabla', 'view'));
        }

        if ($action === 'edit') {
            return view('programacion.create', compact('tecnicos', 'user', 'programacion', 'tabla'));
        }
    }


    public function busqueda($contrato): ?\Illuminate\Http\JsonResponse
    {
        $datos = $this->contratos->buscarEnBase($contrato);

        // null es "sin resultados": la vista lo distingue por el cuerpo vacío.
        return $datos === null ? null : response()->json($datos);
    }


    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $resultado = $this->contratos->crear(
                $request->data, $request->tabla, $request->boolean('quejaConfirmada')
            );
        } catch (QueryException $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al guardar en base de datos. ' . $e->getMessage()]);
        }

        return response()->json($resultado);
    }


    public function update($id, Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $resultado = $this->contratos->actualizarCampo($id, $request->propiedad, $request->valor);
        } catch (QueryException $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al actualizar registro. ' . $e->getMessage()], 500);
        }

        // El servicio marca con `estado` lo que no es un 200.
        $estado = $resultado['estado'] ?? 200;
        unset($resultado['estado']);

        return response()->json($resultado, $estado);
    }


    public function destroy(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $resultado = $this->contratos->eliminar($request->data);
        } catch (QueryException $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al eliminar registro. ' . $e]);
        }

        return response()->json($resultado);
    }


    public function erase($id): \Illuminate\Http\JsonResponse
    {
        try {
            $resultado = $this->programaciones->eliminar($id);
        } catch (QueryException $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al eliminar Programación. ' . $e]);
        }

        return response()->json($resultado);
    }


    public function finish($id): \Illuminate\Http\JsonResponse
    {
        try {
            $resultado = $this->cierre->cerrar($id);
        } catch (QueryException $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al finalizar Programación. ' . $e]);
        }

        if ($resultado !== []) {
            return response()->json(['error' => $resultado['error']], $resultado['estado']);
        }

        session()->flash('success', 'Programación finalizada correctamente');

        return response()->json(['ok' => 'Programación finalizada correctamente']);
    }


    public function buscarPorContrato(Request $request)
    {

        $contrato = $request->input('contrato');
        $array = array();

        try {
            $programadas = tbl_programacion_usuario::whereIn(
                'id',
                tbl_programacion_contrato::select('id_programacion')
                    ->where('CONTRATO', 'LIKE', '%' . $contrato . '%')
            )->get();

            foreach ($programadas as $programada) {
                $usuario = User::find($programada->id_usuario);

                $array[] = [
                    'id' => $programada->id,
                    'nombre' => $programada->nombre,
                    'usuario' => $usuario->name,
                ];
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => $e], 422);
        }
        return response()->json($array);
    }


    public function PlantillaStore(Request $request)
    {
        try {
            $resultado = $this->contratos->crearDesdePlantilla($request->data, $request->tabla);
        } catch (QueryException $e) {
            Log::error($e);

            return response()->json(['error' => 'No se pudo guardar registro. ' . $e->getMessage()], 422);
        }

        return response()->json($resultado, 200);
    }
}
