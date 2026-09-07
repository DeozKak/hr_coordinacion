<?php

namespace App\Http\Controllers\Bitacoras;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bitacoras\GuardarTablaRequest;
use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Bitacoras\TblBitacorasCausal;
use App\Models\Bitacoras\TblDvInsp;
use App\Models\User;
use App\Models\Zonificacion\TblLocalidadesMunicipio;
use App\Notifications\devolucion;
use App\Services\Bitacoras\CargaBitacoraService;
use App\Services\Bitacoras\DevolucionesService;
use App\Services\Bitacoras\ExcelBitacoraService;
use App\Services\Bitacoras\GuardadoBitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BitacoraController extends Controller
{
    public function ver()
    {
        $supervisores = Auth::user();
        $id_user = $supervisores->id;
        if ($supervisores->hasRole('Supervisor')) {

            $temp = TblBitacoraArchivo::where('id_usuario', '=', $id_user)->where('finished', '=', 0)->first();

            if (! $temp) {

                return view('bitacoras.generar', compact('supervisores'));
            }

            session()->flash('warning', 'Ya tienes una bitácora en proceso. ¿Deseas continuar?');

            return view('bitacoras.generar', compact('supervisores', 'temp'));
        }
        $supervisores = User::role('Supervisor')
            ->where('state', 1)
            ->get();

        $temp = TblBitacoraArchivo::where('id_usuario', '=', $id_user)->where('finished', '=', 0)->first();

        if (! $temp) {

            return view('bitacoras.generar', compact('supervisores'));
        }

        session()->flash('warning', 'Ya tienes una bitácora en proceso. ¿Deseas continuar?');

        return view('bitacoras.generar', compact('supervisores', 'temp'));
    }

    /**
     * Carga el Excel de inspecciones y abre el borrador.
     *
     * El trabajo vive en {@see CargaBitacoraService}: aquí sólo se comprueba lo
     * que llega y se decide si la carga es de un supervisor o de todos.
     */
    public function generar_bitacora(Request $request, CargaBitacoraService $carga)
    {
        $validator = Validator::make($request->all(), [
            'supervisor' => 'required',
            'archivo' => 'required|file',
        ], [
            'supervisor.required' => 'Por favor seleccione un supervisor',
            'archivo.required' => 'Por favor seleccione un archivo',
            'archivo.file' => 'El archivo seleccionado no es válido',
        ]);

        if ($validator->fails()) {
            return redirect()->route('bitacora')
                ->withErrors($validator)->withInput()
                ->with('error', $validator->errors()->first());
        }

        /* El cero no es un supervisor: significa cargar la bitácora de todos. */
        $supervisor = $request->input('supervisor') === CargaBitacoraService::TODOS
            ? null
            : User::find($request->input('supervisor'));

        try {
            $datos = $carga->procesar($request->file('archivo'), $supervisor);
        } catch (\RuntimeException $e) {
            return redirect()->route('bitacora')->with('error', $e->getMessage());
        }

        return view('bitacoras.tabla', $datos);
    }


    public function guardar_tabla(GuardarTablaRequest $request, GuardadoBitacoraService $guardado, ?User $super = null)
    {
        $usuario = Auth::user();
        $bitacora = TblBitacoraArchivo::where('id_usuario', $usuario->id)
            ->where('finished', 0)
            ->first();

        if (! $bitacora) {
            return response()->json(['error' => 'No hay ninguna bitácora en curso.'], 404);
        }

        /* Sin supervisor la bitácora se descarta: es la vía por la que la
           pantalla cancela lo que se llevaba diligenciado. */
        if ($super === null) {
            $guardado->descartar($bitacora);

            return response()->json(['ruta' => route('bitacora')]);
        }

        try {
            $resumen = $guardado->guardar($bitacora, $super);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['error' => 'No se pudo guardar la bitácora.'], 500);
        }

        $guardado->avisar($resumen['bitacora'], $usuario);

        session()->flash('success', 'Bitacora generada correctamente');

        return response()->json([
            'ruta' => route('bitacora'),
            'resumen' => $resumen,
            'descarga' => route('bitacoras.download', ['idBitacora' => $resumen['bitacora']]),
        ]);
    }

    public function borrar_archivos()
    {
        $directorio = storage_path('app/uploads/');

        $archivos = array_diff(scandir($directorio), ['.', '..']);

        if (count($archivos) > 4) {

            usort($archivos, function ($a, $b) use ($directorio) {
                return filemtime("$directorio/$a") - filemtime("$directorio/$b");
            });

            // Calcular cuántos archivos se deben eliminar
            $numArchivosABorrar = count($archivos) - 4;

            for ($i = 0; $i < $numArchivosABorrar; $i++) {
                // Ruta completa del archivo a borrar
                $archivoABorrar = "$directorio/{$archivos[$i]}";

                // Verificar si es un archivo antes de intentar eliminarlo
                if (is_file($archivoABorrar)) {
                    // Borrar el archivo
                    unlink($archivoABorrar);
                }
            }

            return 'Archivos depurados';
        } else {
            return 'No es necesario Depurar';
        }
    }

    public function devoluciones(DevolucionesService $devoluciones)
    {
        return view('bitacoras.devoluciones', [
            'devoluciones' => $devoluciones->pendientes(),
            'gestionados' => $devoluciones->gestionadas(),
        ]);
    }


    /**
     * Excel de seguimiento de devoluciones.
     *
     * Se arma desde la base y se entrega en la respuesta. Antes lo construía a
     * partir del HTML que enviaba el navegador y lo dejaba escrito en
     * `storage/app/uploads` para servirlo con un enlace firmado.
     */
    public function exportar_tabla_devoluciones(DevolucionesService $devoluciones)
    {
        $libro = $devoluciones->exportar();

        return response()->streamDownload(function () use ($libro) {
            IOFactory::createWriter($libro, 'Xlsx')->save('php://output');
        }, $devoluciones->nombreDelExcel(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


    public function reportes()
    {
        $bitacoras = TblBitacoraArchivo::where('finished', '=', '1')->get()->map(function ($bitacora) {
            $bitacora->fecha_creacion = $bitacora->created_at->format('Y-m-d');

            return $bitacora;
        });

        return view('bitacoras.reportes', compact('bitacoras'));
    }

    public function verReporte($id_bitacora)
    {
        $bitacora = TblBitacoraArchivo::find($id_bitacora);
        $causales_dv = TblBitacorasCausal::all();

        if ($bitacora == null) {
            return redirect()->route('bitacoras.reportes')->with('error', 'Bitacora no encontrada');
        }

        return view('bitacoras.verReporte', compact('bitacora', 'causales_dv'));
    }

    public function consultaReporte($id_bitacora)
    {
        // contratos asignados a la bitacora
        $contratos = TblBitacoraContrato::selectRaw("CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS nombre_completo, tbl_bitacora_contratos.id,tbl_bitacora_contratos.CC_OPERARIO, tbl_bitacora_contratos.MUNICIPIO, tbl_bitacora_contratos.FECHA, tbl_bitacora_contratos.No_ACTA, tbl_bitacora_contratos.TIPO_TRABAJO, tbl_bitacora_contratos.CONTRATO, tbl_bitacora_contratos.ORDEN_TRABAJO, tbl_bitacora_contratos.ORDEN_EXT, tbl_bitacora_contratos.CATEGORIA, tbl_bitacora_contratos.RESULTADO_CIERRE, tbl_bitacora_contratos.HORA_INICIO, tbl_bitacora_contratos.HORA_FINAL, tbl_bitacora_contratos.DURACION_INSP,
                        CASE
                        WHEN tbl_bitacora_contratos.vence IS NOT NULL THEN tbl_bitacora_contratos.vence
                        WHEN tbl_bitacora_contratos.PERIODO_GRACIA = 1 THEN 'PERIODO DE GRACIA'
                        ELSE NULL  -- o '' si prefieres un valor vacío
                        END AS vence,
                        tbl_bitacora_contratos.CAUSAL_RECHAZO")
            ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_contratos.CC_OPERARIO')
            ->where('tbl_bitacora_contratos.id_bitacora', $id_bitacora)
            ->get();

        return response()->json(['contratos' => $contratos]);
    }

    public function ConsultaIndicadores($id_bitacora)
    {
        // contadores de cierres
        $certificadas = TblBitacoraContrato::where('id_bitacora', $id_bitacora)->where('RESULTADO_CIERRE', 'CERTIFICADA')->count();
        $certificadasConNovedades = TblBitacoraContrato::where('id_bitacora', $id_bitacora)->where('RESULTADO_CIERRE', 'CERTIFICADA CON NOVEDADES')->count();
        $inspeccionadasConDefectoCritico = TblBitacoraContrato::where('id_bitacora', $id_bitacora)->where('RESULTADO_CIERRE', 'INSPECCIONADA CON DEFECTO CRITICO VALLE')->count();
        $inspeccionadasConDefectoNoCritico = TblBitacoraContrato::where('id_bitacora', $id_bitacora)->where('RESULTADO_CIERRE', 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE')->count();
        $totalContratosOK = TblBitacoraContrato::where('id_bitacora', $id_bitacora)->count();

        return response()->json([
            'certificadas' => $certificadas,
            'certificadasConNovedades' => $certificadasConNovedades,
            'inspeccionadasConDefectoCritico' => $inspeccionadasConDefectoCritico,
            'inspeccionadasConDefectoNoCritico' => $inspeccionadasConDefectoNoCritico,
            'totalContratosOK' => $totalContratosOK,
        ]);
    }

    /**
     * Resuelve una devolución y, si se pide, la devuelve a producción.
     */
    public function actualizar_devolucion(Request $request, $id, DevolucionesService $devoluciones)
    {
        $devolucion = TblDvInsp::find($id);

        /* Sin esto, un identificador que ya no existe —una devolución borrada,
           un enlace viejo— terminaba escribiendo sobre null. */
        if (! $devolucion) {
            abort(404, 'La devolución no existe.');
        }

        $devoluciones->gestionar(
            $devolucion,
            $request->input('observacion'),
            $request->input('agregar_produccion') === '1'
        );

        return redirect()->route('bitacora.devoluciones');
    }


    public function buscarPorContrato(Request $request)
    {

        $contrato = $request->input('contrato');

        $bitacoras = TblBitacoraArchivo::whereIn(
            'id',
            TblBitacoraContrato::select('id_bitacora')
                ->where('CONTRATO', 'LIKE', '%'.$contrato.'%')
        )->get();

        // Devolver resultados en formato JSON
        return response()->json($bitacoras);
    }

    public function getMunicipiosJson(Request $request)
    {
        $term = $request->input('term');

        $municipios = TblLocalidadesMunicipio::where('nombre', 'like', "%$term%")
            ->pluck('nombre', 'nombre'); // Obtener nombre e ID

        return response()->json($municipios);
    }

    /**
     * Arma el Excel de una bitácora en el momento en que alguien lo pide.
     *
     * Antes el archivo se escribía al guardar y esta acción sólo lo servía del
     * disco, con lo que el servidor acumulaba un `.xlsx` por bitácora aunque
     * nadie lo abriera. Ahora se construye desde la base y se entrega en la
     * respuesta: no queda nada guardado y el contenido está al día.
     */
    public function download($idBitacora, ExcelBitacoraService $excel)
    {
        $bitacora = TblBitacoraArchivo::find($idBitacora);

        if (! $bitacora) {
            return redirect()->route('bitacoras.reportes')->with('error', 'Bitácora no encontrada');
        }

        $libro = $excel->construir($bitacora);
        $nombre = $excel->nombreArchivo($bitacora);

        return response()->streamDownload(function () use ($libro) {
            IOFactory::createWriter($libro, 'Xlsx')->save('php://output');
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Devuelve contratos de una bitácora a sus inspectores.
     */
    public function devolver(Request $request, $ids, $bitacora, DevolucionesService $devoluciones)
    {
        try {
            $contratos = $devoluciones->devolver(
                array_filter(explode(',', (string) $ids)),
                (int) $bitacora,
                $request->input('causal')
            );
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al devolver los contratos'], 500);
        }

        return response()->json($contratos);
    }
}
