<?php

namespace App\Http\Controllers\Bitacoras;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bitacoras\ActualizarBorradorRequest;
use App\Http\Requests\Bitacoras\AgregarInspeccionRequest;
use App\Models\Bitacoras\TblBitacorasCausal;
use App\Models\Bitacoras\TblTempContrato;
use App\Models\TblInspCali;
use App\Models\Zonificacion\TblLocalidadesMunicipio;
use App\Services\Bitacoras\AutoguardadoService;
use Illuminate\Support\Facades\Log;

class AutoGuardadoController extends Controller
{
    public function Restaurar($id_bitacora, AutoguardadoService $autoguardado)
    {
        /* El borrador es de quien lo abrió. Antes bastaba el identificador para
           abrir el de cualquier otra persona y ver sus inspecciones. */
        try {
            $autoguardado->borradorPropio((int) $id_bitacora);
        } catch (\DomainException $e) {
            return redirect()->route('bitacora')->with('error', $e->getMessage());
        }
        try {
            $super = TblTempContrato::select('id_super')->where('id_bitacora', $id_bitacora)->first();
            $id_super = $super->id_super;
            $inspectores = TblInspCali::where('SUPERVISOR', $id_super)
                ->where('state', 1)
                ->orderBy('apellidos', 'asc')
                ->get();
        } catch (\Exception $e) {
            return redirect()->route('bitacora')->with('error', 'Error en el proceso, no se puede identificar el supervisor
            por favor vuelve a generar la bitácora');
        }
        foreach ($inspectores as $inspector) {
            $nombres[] = $inspector->apellidos.' '.$inspector->nombres;
            $cedulas[] = $inspector->cedula;
            $ids[$inspector->cedula] = $inspector->id;
        }

        session(['ids_inspectores' => $ids]);

        $municipios = TblLocalidadesMunicipio::all();
        $response = TblTempContrato::where('id_bitacora', $id_bitacora)->get();
        $causales = TblBitacorasCausal::all();

        return view('bitacoras.tabla', compact('response', 'nombres', 'municipios', 'causales', 'id_super', 'inspectores', 'cedulas'));
    }

    public function Borrar($id_bitacora, AutoguardadoService $autoguardado)
    {
        try {
            $bitacora = $autoguardado->borradorPropio((int) $id_bitacora);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $autoguardado->descartar($bitacora);

        return response()->json(['success' => 'Datos eliminados correctamente']);
    }

    /**
     * Guarda un cambio del borrador y devuelve cómo quedó almacenado.
     *
     * La respuesta lleva el valor persistido para que la pantalla pinte ese y
     * no el suyo: hasta ahora daba el cambio por bueno al escribirlo y sólo
     * avisaba si fallaba, de modo que un fallo dejaba a la vista un dato que
     * el servidor nunca llegó a guardar.
     */
    public function Actualizar($id, ActualizarBorradorRequest $request, AutoguardadoService $autoguardado)
    {
        try {
            $guardado = $autoguardado->actualizarCampo((int) $id, $request->input('campo'), $request->input('valor'));
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['error' => 'No se pudo guardar el cambio.'], 500);
        }

        return response()->json($guardado);
    }

    public function Agregar(AgregarInspeccionRequest $request, AutoguardadoService $autoguardado)
    {
        try {
            $fila = $autoguardado->agregarInspeccion($request->input('datos'));
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['error' => 'Error al guardar los datos'], 500);
        }

        return response()->json(['id' => $fila->id]);
    }
}
