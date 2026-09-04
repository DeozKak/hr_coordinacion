<?php

namespace App\Http\Controllers\Programacion;

use App\Http\Controllers\Controller;

use App\Models\tbl_insp_cali;
use App\Services\Programacion\AgendamientoService;
use App\Services\Programacion\PlantillaGdwService;
use App\Services\Programacion\PlantillaInvalida;
use App\Services\Programacion\ReAsignacion;
use App\Services\Programacion\ReporteSupervisorService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * La pantalla de agendamiento y lo que sale de ella.
 *
 * Consultar lo agendado para una fecha o un rango, y sacarlo en los tres
 * formatos que se usan: la plantilla de GDW, el reparto por supervisor y el
 * archivo de reasignación.
 */
class ProgramacionAgendamientoController extends Controller
{
    public function __construct(
        private AgendamientoService $agendamientos,
        private PlantillaGdwService $plantillaGdw,
        private ReporteSupervisorService $reporteSupervisor
    ) {
        $this->middleware('auth');
    }


    public function detalles()
    {
        $tecnicos = tbl_insp_cali::where('state','1')->get();
        return view('programacion.ver',compact('tecnicos'));
    }


    public function agendamiento(Request $request): \Illuminate\Http\JsonResponse
    {
        $datos = $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin'    => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        try {
            return response()->json(
                $this->agendamientos->consultar($datos['fechaInicio'], $datos['fechaFin'] ?? null)
            );
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al consultar agendamiento. ' . $e->getMessage()], 422);
        }
    }


    public function exportar(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $archivo = $this->plantillaGdw->generar($request->data);
        } catch (PlantillaInvalida $e) {
            // Fila que no se puede exportar: el mensaje ya dice cuál.
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar archivo. ' . $e->getMessage()], 500);
        }

        return response()->json([
            'url' => url()->temporarySignedRoute(
                'descargar.archivo',
                now()->addMinutes(10),
                ['file' => $archivo]
            ),
        ]);
    }


    public function exportarSup(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $archivo = $this->reporteSupervisor->generar(
                $request->data,
                $request->fechaInicio,
                $request->fechaFin
            );
        } catch (PlantillaInvalida $e) {
            // Fila sin técnico: el mensaje ya dice cuál.
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al generar archivo. ' . $e->getMessage()], 500);
        }

        return response()->json([
            'url' => url()->temporarySignedRoute(
                'descargar.archivo',
                now()->addMinutes(10),
                ['file' => $archivo]
            ),
        ]);
    }


    public function ReAsignarProgramacion($fecha, ReAsignacion $programacionService)
    {
            // 1. Validar la fecha
            $validator = Validator::make(['fecha' => $fecha], [
                'fecha' => 'required|date_format:Y-m-d'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Formato de fecha inválido. Debe ser AAAA-MM-DD'], 400);
            }

            // 2. Llamar al servicio para que haga todo el trabajo
            $respuestaExcel = $programacionService->procesarYExportar($fecha);
            // 3. Verificar si hubo datos
        if (!$respuestaExcel) {
                return response()->json(['mensaje' => 'No hay programaciones para esta fecha.'], 404);
        }
        // 4. Retornar el Excel directamente al usuario
        return $respuestaExcel;
    }
}
