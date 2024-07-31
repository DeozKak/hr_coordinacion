<?php

namespace App\Http\Controllers;

use App\Models\tbl_bitacoras_causal;
use App\Models\tbl_localidades_municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\tbl_bitacora_contrato;
use App\Models\tbl_insp_cali;
use App\Models\tbl_bitacora_archivo;
use App\Models\tbl_temp_contrato;

class AutoGuardadoController extends Controller
{
    public function buscar($nombre)
    {

        $archivo = tbl_bitacora_archivo::where('NOMBRE_ARCHIVO', $nombre)
            ->where('finished', '=', '1')->exists();

        return $archivo;
    }

    public function guardar($spreadsheet, $nombres, $super)
    {
        $rutaArchivo = str_replace(".xls", " ", session('nom_archivo'));
        $rutaArchivoFinal = str_replace("4.08", "", $rutaArchivo);
        $nombreArchivo = $rutaArchivoFinal . ".xlsx";

        $usuario = Auth::user();
      
        try {
            $bitacora = tbl_bitacora_archivo::where('id_usuario', $usuario->id)->where('finished', '=', 0)->first();
            if (!$bitacora) {
                $bitacora = new tbl_bitacora_archivo();
                $bitacora->id_usuario = $usuario->id;
                $bitacora->NOMBRE_ARCHIVO = $rutaArchivoFinal;
                $bitacora->ruta_archivo = 'storage/app/uploads/' . $nombreArchivo;
                $bitacora->save();
            }
        } catch (\Exception $e) {
            throw $e;
        }
        $datos = [];

        foreach ($nombres as $nombre) {
            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                foreach ($sheet->getRowIterator() as $row) {
                    $contrato = $sheet->getCell('H' . $row->getRowIndex())->getValue();
                    $nombreCelda = $sheet->getCell('A' . $row->getRowIndex())->getValue();
                    $cc = $sheet->getCell('B' . $row->getRowIndex())->getValue(); // Índice potencial
                    $vence = $sheet->getCell('Q' . $row->getRowIndex())->getValue();
                    $cierre = ltrim($sheet->getCell('M' . $row->getRowIndex())->getValue(), '.');

                    // Verificar condiciones de filtrado
                    if (
                        strpos($contrato, ":") === 0 &&
                        $nombreCelda === $nombre &&
                        in_array($cierre, ["CERTIFICADA", "CERTIFICADA CON NOVEDADES", "INSPECCIONADA CON DEFECTO CRITICO VALLE", "INSPECCIONADA CON DEFECTO NO CRITICO VALLE"])
                    ) {
                        $filaDatos = []; // Array para almacenar datos de la fila

                        foreach (['A', 'B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'M', 'N', 'O', 'Q'] as $columna) {
                            $valor = $sheet->getCell($columna . $row->getRowIndex())->getValue();

                            if ($columna === 'M') {
                                $valor = ltrim($sheet->getCell($columna . $row->getRowIndex())->getValue(), '.');
                            }
                            if ($columna === 'D' && is_numeric($valor)) {
                                $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor);
                                $valor = $fecha->format('y-m-d');
                            }

                            // Formateo especial para vencimiento
                            if ($columna === 'Q') {
                                $venceDate = \DateTime::createFromFormat('d/m/Y', $valor);
                                $valor = ($venceDate && $venceDate->format('Y') == date('Y') && $venceDate->format('m') == date('m')) ? "60 meses" : "";
                            }

                            $filaDatos[$columna] = $valor;
                        }

                        // Usar el valor de la columna 'B' como índice
                        $datos[$cc][] = $filaDatos;
                    }
                }
            }
        }

        try {
            foreach ($datos as $cc => $inspecciones) {
                foreach ($inspecciones as $inspeccion) {
                    $existe = tbl_temp_contrato::where('CONTRATO', $inspeccion['H'])
                        ->where('ORDEN_TRABAJO', $inspeccion['I'])
                        ->where('No_ACTA', $inspeccion['E'])
                        ->where('TIPO_TRABAJO', $inspeccion['G'])
                        ->exists();
                    if (!$existe) {


                        tbl_temp_contrato::create([
                            'NOMBRE' => $inspeccion['A'],
                            'CC_OPERARIO' => $cc,
                            'MUNICIPIO' => $inspeccion['C'],
                            'FECHA' => $inspeccion['D'],
                            'No_ACTA' => $inspeccion['E'],
                            'TIPO_TRABAJO' => $inspeccion['G'],
                            'CONTRATO' => $inspeccion['H'],
                            'ORDEN_TRABAJO' => $inspeccion['I'],
                            'ORDEN_EXT' => $inspeccion['J'],
                            'CATEGORIA' => $inspeccion['K'],
                            'RESULTADO_CIERRE' => $inspeccion['M'],
                            'HORA_INICIO' => $inspeccion['N'],
                            'HORA_FINAL' => $inspeccion['O'],
                            'VENCE' => $inspeccion['Q'],
                            'id_bitacora' => $bitacora->id,
                            'id_usuario' => $usuario->id,
                            'id_super' => $super ?? 1 
                        ]);
                    }
                }
            }
            $datosDB = tbl_temp_contrato::where('id_bitacora', $bitacora->id)->get();
        } catch (\Exception $e) {
            throw $e;
        }
        return $datosDB;
    }

    public function Restaurar($id_bitacora)
    {
        try {
            $archivo = tbl_bitacora_archivo::select('finished')->where('id', $id_bitacora)->first();

            if ($archivo->finished === 1) {
                return null;
            }
        } catch (\Exception $e) {
            return redirect()->route('bitacora')->with('error', 'Error en el proceso');
        }
        $super = tbl_temp_contrato::select('id_super')->where('id_bitacora', $id_bitacora)->first();
        $id_super = $super->id_super;
        $inspectores = tbl_insp_cali::where('SUPERVISOR', $id_super)
            ->where('state', 1)
            ->orderBy('apellidos', 'asc')
            ->get();

        foreach ($inspectores as $inspector) {
            $nombres[] = $inspector->apellidos . ' ' . $inspector->nombres;
            $ids[$inspector->cedula] = $inspector->id;
        }

        session(['ids_inspectores' => $ids]);

        $municipios = tbl_localidades_municipio::all();
        $response = tbl_temp_contrato::where('id_bitacora', $id_bitacora)->get();
        $causales = tbl_bitacoras_causal::all();

        return view('bitacoras.tabla', compact('response', 'nombres', 'municipios', 'causales', 'id_super', 'inspectores'));
    }

    public function Borrar($id_bitacora)
    {
        try {
            $contratos = tbl_temp_contrato::where('id_bitacora', $id_bitacora)->get();
            foreach ($contratos as $contrato) {
                $contrato->delete();
            }
            $archivo = tbl_bitacora_archivo::where('id', $id_bitacora)->first();
            $archivo->delete();
        } catch (\Exception $e) {
            throw $e;
        }
        return response()->json(['success' => 'Datos eliminados correctamente']);
    }

    public function Actualizar($id, Request $request)
    {

        $campo = $request->campo;
        $valor = $request->valor;

        try {
            $contrato = tbl_temp_contrato::find($id);

            $contrato->$campo = $valor;
            $contrato->save();
        } catch (\Exception $e) {
            throw $e;
        }
        return response()->json(['success' => 'Datos actualizados correctamente']);
    }

    public function Agregar(Request $request)
    {
        $user = Auth::user();

        $datos = $request->datos;
        try {
            if ($datos['cantidadRecintos'] === null) {
                $datos['cantidadRecintos'] = "NO";
            }

            $contrato = new tbl_temp_contrato();
            $contrato->NOMBRE = $datos['nombre'];
            $contrato->CC_OPERARIO = $datos['cedula'];
            $contrato->MUNICIPIO = $datos['municipio'];
            $contrato->FECHA = $datos['fecha'];
            $contrato->No_ACTA = $datos['acta'];
            $contrato->TIPO_TRABAJO = $datos['tipoTrabajo'];
            $contrato->CONTRATO = $datos['contrato'];
            $contrato->CATEGORIA = $datos['categoria'];
            $contrato->{'4_RECINTOS'} = $datos['cantidadRecintos'];
            $contrato->RESULTADO_CIERRE = $datos['resultadoCierre'];
            $contrato->CAUSAL_RECHAZO = $datos['rechazo'];
            $contrato->id_bitacora = $datos['id_bitacora'];
            $contrato->id_super = $datos['id_super'];
            $contrato->id_usuario = $user->id;
            $contrato->save();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al guardar los datos']);
        }

        return response()->json(['id' => $contrato->id]);
    }
}
