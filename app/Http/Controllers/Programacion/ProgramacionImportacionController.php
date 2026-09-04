<?php

namespace App\Http\Controllers\Programacion;

use App\Http\Controllers\Controller;

use App\Jobs\ProcessExcelFileMacros;
use App\Services\Programacion\Importacion\CallCenterGdoService;
use App\Services\Programacion\Importacion\CargaBaseService;
use App\Services\Programacion\Importacion\Formatos;
use App\Services\Programacion\Importacion\LectorDeCabeceras;
use App\Services\Programacion\Importacion\ProgramacionMasivaService;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Las tres subidas de Excel del módulo de programación.
 *
 * La base de GDO, la programación masiva de técnicos y el archivo del call
 * center. Las tres siguen el mismo guion: se comprueba que el archivo tenga
 * las cabeceras del formato que dice ser y se entrega al servicio que
 * corresponda, que lo vuelca o lo encola.
 */
class ProgramacionImportacionController extends Controller
{
    public function __construct(
        private LectorDeCabeceras $cabeceras,
        private CargaBaseService $cargaBase,
        private ProgramacionMasivaService $masivas,
        private CallCenterGdoService $callCenter
    ) {
        $this->middleware('auth');
    }


    public function base(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xls,xlsx',
        ], [
            'archivo.required' => 'El campo archivo es obligatorio.',
            'archivo.file'     => 'El valor debe ser un archivo.',
            'archivo.mimes'    => 'El archivo debe ser de tipo XLS o XLSX.',
        ]);

        try {
            // Con la casilla marcada el archivo es la base de GDO y se vuelca aquí mismo.
            if ($request->input('check_estado5') == 1) {
                return $this->volcarBaseGdo($request->file('archivo'));
            }

            return $this->encolarBaseParaMacros($request->file('archivo'));
        } catch (\Exception $e) {
            Log::error('Error en la subida inicial del archivo: ' . $e->getMessage());

            return response()->json(['errors' => 'No se pudo procesar la solicitud de subida.'], 500);
        }
    }

    /** Base de GDO: se valida y se inserta en el momento. */


    /** Base de GDO: se valida y se inserta en el momento. */
    private function volcarBaseGdo($archivo): \Illuminate\Http\JsonResponse
    {
        $hoja = IOFactory::load($archivo)->getActiveSheet();
        $formato = Formatos::gdo();

        if (! $formato->coincide($this->cabeceras->deLaHoja($hoja, $formato))) {
            return response()->json(['errors' => 'El archivo no cumple con el formato requerido'], 422);
        }

        return $this->cargaBase->cargar($hoja)
            ? response()->json(['message' => 'Se ha cargado correctamente la base de datos'], 200)
            : response()->json(['errors' => 'Error al cargar la base de datos'], 422);
    }

    /**
     * Base para las macros: sólo se comprueban las cabeceras y se encola.
     *
     * La validación se hace leyendo en flujo la primera fila, sin cargar el
     * libro entero: estos archivos son grandes y el proceso pesado va aparte.
     */


    /**
     * Base para las macros: sólo se comprueban las cabeceras y se encola.
     *
     * La validación se hace leyendo en flujo la primera fila, sin cargar el
     * libro entero: estos archivos son grandes y el proceso pesado va aparte.
     */
    private function encolarBaseParaMacros($archivo): \Illuminate\Http\JsonResponse
    {
        $ruta = $archivo->store('excel-imports');
        $formato = Formatos::macros();

        $lector = ReaderEntityFactory::createXLSXReader();
        $lector->open(storage_path('app/' . $ruta));

        $primeraFila = [];
        foreach ($lector->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $fila) {
                $primeraFila = $this->cabeceras->deLaFila($fila->toArray());
                break;
            }
            break;
        }
        $lector->close();

        if (! $formato->coincide($primeraFila)) {
            Storage::delete($ruta);   // no se deja basura en el disco

            return response()->json(['errors' => 'La estructura del archivo o los encabezados no son correctos.'], 422);
        }

        ProcessExcelFileMacros::dispatch($ruta, Auth::user(), $archivo->getClientOriginalName());

        return response()->json(['message' => 'El archivo ha sido aceptado y se está procesando en segundo plano.'], 202);
    }


    public function masivos(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xls,xlsx',
        ], [
            'archivo.required' => 'El campo archivo es obligatorio.',
            'archivo.file'     => 'El valor debe ser un archivo.',
            'archivo.mimes'    => 'El archivo debe ser de tipo XLS o XLSX.',
        ]);

        $hoja = IOFactory::load($request->file('archivo'))->getActiveSheet();
        $formato = Formatos::masivos();
        $primeraFila = $this->cabeceras->deLaHoja($hoja, $formato);

        if (! $formato->coincide($primeraFila)) {
            return response()->json(['errors' => $formato->explicarError($primeraFila)], 422);
        }

        $resultado = $this->masivas->cargar($hoja);

        if ($resultado !== true) {
            return response()->json(['errors' => $resultado], 422);
        }

        session()->flash('success', 'Archivo subido exitosamente');

        return response()->json(['message' => 'Archivo subido exitosamente']);
    }



    public function callCenterGdo(Request $request): \Illuminate\Http\JsonResponse
    {
        $validador = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:xls,xlsx',
        ], [
            'archivo.required' => 'El archivo es requerido',
            'archivo.mimes'    => 'El archivo debe ser un archivo excel',
            'archivo.file'     => 'la entrada debe ser un archivo',
        ]);

        if ($validador->fails()) {
            return response()->json(['error' => $validador->errors()], 422);
        }

        $hoja = IOFactory::load($request->file('archivo'))->getActiveSheet();

        if (! $this->callCenter->formatoCorrecto($hoja)) {
            return response()->json(['error' => 'El archivo no cumple los criterios requeridos'], 422);
        }

        try {
            $this->callCenter->encolar($request->file('archivo'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al iniciar proceso: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'El archivo se está procesando con IA en segundo plano. Te notificaremos cuando termine.',
        ], 202);
    }
}
