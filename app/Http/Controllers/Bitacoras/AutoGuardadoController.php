<?php

namespace App\Http\Controllers\Bitacoras;

use App\Http\Controllers\Controller;
use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblBitacorasCausal;
use App\Models\Bitacoras\TblDvInsp;
use App\Models\Bitacoras\TblTempContrato;
use App\Models\Bitacoras\TblTempFallida;
use App\Models\TblInspCali;
use App\Models\TblQuejasContrato;
use App\Models\User;
use App\Models\Zonificacion\TblLocalidadesMunicipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AutoGuardadoController extends Controller
{
    public function buscar($nombre)
    {

        $archivo = TblBitacoraArchivo::where('NOMBRE_ARCHIVO', $nombre)
            ->where('finished', '=', '1')->exists();

        if ($archivo) {
            return response()->json(['error' => 'El archivo seleccionado ya ha sido procesado']);
        }

        $proceso = TblBitacoraArchivo::where('NOMBRE_ARCHIVO', $nombre)
            ->where('finished', '=', '0')->exists();

        if ($proceso) {
            return response()->json(['error' => 'El archivo seleccionado se encuentra en proceso por otro usuario']);
        }
    }

    public function guardar($spreadsheet, $nombres, $super, $cedulas, $cierre_todos = null)
    {
        $rutaArchivoFinal = str_replace(['.xls', '4.08', ' V10'], [' ', '', ''], session('nom_archivo'));
        $nombreArchivo = $rutaArchivoFinal . ".xlsx";

        $usuario = Auth::user();


        try {
            $bitacora = TblBitacoraArchivo::where('id_usuario', $usuario->id)->where('finished', '=', 0)->first();
            if (!$bitacora) {
                $bitacora = new TblBitacoraArchivo();
                $bitacora->id_usuario = $usuario->id;
                $bitacora->NOMBRE_ARCHIVO = $rutaArchivoFinal;
                $bitacora->ruta_archivo = 'storage/app/uploads/' . $nombreArchivo;
                $bitacora->save();
            }
        } catch (\Exception $e) {
            throw $e;
        }
        $datos = [];
        $DatosFallidas = [];
        $DatosQuejas = [];
        $arrayFallidas = [
            'EJECUTADA',
            '.ANULADO VALLE',
            '.DIRECCION NO ENCONTRADA',
            '.PREDIO EN CONSTRUCCION',
            'APLAZADO POR EL USUARIO.',
            'CASA SOLA.',
            'CERTIFICADA POR EYC.',
            'CERTIFICADA POR OIA EXTERNO.',
            'MEDIDOR POR LITROS BORRADOS.',
            'MENOR DE EDAD.',
            'NO ESTA EL ENCARGADO.',
            'NOVEDAD BLOQUEANTE',
            'NOVEDAD BLOQUEANTE.',
            'PERDIDA',
            'PREDIO DESOCUPADO.',
            'PROGRAMADA.',
            'USUARIO NO AUTORIZA.'
        ];
        $columnas = ['A', 'B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'M', 'N', 'O', 'Q', 'R', 'S'];
        foreach ($nombres as $index => $nombre) {
            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                foreach ($sheet->getRowIterator() as $row) {
                    if ($row->getRowIndex() === 1) continue;

                    $contrato = $sheet->getCell('H' . $row->getRowIndex())->getValue();
                    $nombreCelda = $sheet->getCell('A' . $row->getRowIndex())->getValue();
                    $cc_operario = $sheet->getCell('B' . $row->getRowIndex())->getValue(); // Índice potencial
                    $vence = $sheet->getCell('Q' . $row->getRowIndex())->getValue();
                    $cierre = ltrim($sheet->getCell('M' . $row->getRowIndex())->getValue(), '.');
                    $tipo_trabajo = $sheet->getCell('G' . $row->getRowIndex())->getValue();

                    // Verificar condiciones de filtrado
                    if (
                        strpos($contrato, ":") === 0 &&
                        trim($cc_operario) === $cedulas[$index] &&
                        in_array($cierre, ["CERTIFICADA", "CERTIFICADA CON NOVEDADES", "INSPECCIONADA CON DEFECTO CRITICO VALLE", "INSPECCIONADA CON DEFECTO NO CRITICO VALLE"])

                    ) {
                        $filaDatos = []; // Array para almacenar datos de la fila

                        foreach ($columnas as $columna) {
                            $valor = $sheet->getCell($columna . $row->getRowIndex())->getValue();

                            if ($columna === 'S' && $sheet->getCell('G' . $row->getRowIndex())->getValue() === 'RN 12162') {
                                $valor = $sheet->getCell('T' . $row->getRowIndex())->getValue();
                            }
                            //si el tipo de trabajo es 12162 entonces que coja la categoria de la columna L
                            if ($columna === 'K' && $sheet->getCell('G' . $row->getRowIndex())->getValue() === 'RN 12162') {
                                $valor = $sheet->getCell('L' . $row->getRowIndex())->getValue();
                            }
                            if ($columna === 'A') {
                                $valor = trim($valor);
                            }
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

                            if ($columna === 'R') {
                                if ($valor === 'Si') {
                                    $valor = 1;
                                } else {
                                    $valor = 0;
                                }
                            }
                            $filaDatos[$columna] = $valor;
                        }

                        // Usar el valor de la columna 'B' como índice
                        $datos[$cc_operario][] = $filaDatos;
                    } elseif (
                        in_array($tipo_trabajo, ['RP 10444', 'RN 12162', 'RP 12161', 'SA 12163', 'SA 12164']) &&
                        $cierre_todos !== '0' &&
                        strpos($contrato, ":") === 0 &&
                        trim($cc_operario) === $cedulas[$index] &&
                        in_array($cierre, $arrayFallidas)

                    ) {
                        $filaDatosFallidas = []; // Array para almacenar datos de la fila
                        foreach ($columnas as $columna) {
                            $valor = $sheet->getCell($columna . $row->getRowIndex())->getValue();
                            if ($columna === 'A') {
                                $valor = trim($valor);
                            }
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

                            $filaDatosFallidas[$columna] = $valor;
                        }
                        $DatosFallidas[$cc_operario][] = $filaDatosFallidas;
                    } elseif (
                        $tipo_trabajo === 'QUEJAS VALLE ' &&
                        $cierre_todos !== '0' &&
                        // strpos($contrato, ":") === 1 &&
                        trim($cc_operario) === $cedulas[$index] &&
                        in_array($cierre, $arrayFallidas)
                    ) {
                        $filaDatosQuejas = [];
                        foreach ($columnas as $columna) {
                            $valor = $sheet->getCell($columna . $row->getRowIndex())->getValue();
                            if ($columna === 'A') {
                                $valor = trim($valor);
                            }
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

                            $filaDatosQuejas[$columna] = $valor;
                        }

                        $DatosQuejas[$cc_operario][] = $filaDatosQuejas;
                    }
                }
            }
        }

        try {

            if ($cierre_todos !== '0') {
                foreach ($DatosQuejas as $cc => $inspecciones) {
                    foreach ($inspecciones as $inspeccion) {

                        $existe = TblQuejasContrato::where('CONTRATO', $inspeccion['H'])
                            ->where('ORDEN_TRABAJO', $inspeccion['I'])
                            ->where('No_ACTA', $inspeccion['E'])
                            ->where('TIPO_TRABAJO', $inspeccion['G'])
                            ->exists();
                        if (!$existe) {
                            TblQuejasContrato::create([
                                'NOMBRE' => $inspeccion['A'],
                                'CC_OPERARIO' => $inspeccion['B'],
                                'MUNICIPIO' => $inspeccion['C'],
                                'FECHA' => $inspeccion['D'],
                                'No_ACTA' => $inspeccion['E'],
                                'TIPO_TRABAJO' => $inspeccion['G'],
                                'CONTRATO' => ltrim($inspeccion['H'], ':'),
                                'ORDEN_TRABAJO' => $inspeccion['I'],
                                'ORDEN_EXT' => $inspeccion['J'],
                                'CATEGORIA' => $inspeccion['K'],
                                'RESULTADO_CIERRE' => $inspeccion['M'],
                            ]);
                        }
                    }
                }
            }
            if ($cierre_todos !== '0') {
                foreach ($DatosFallidas as $cc => $inspecciones) {
                    foreach ($inspecciones as $inspeccion) {

                        $existe = TblTempFallida::where('CONTRATO', $inspeccion['H'])
                            ->where('ORDEN_TRABAJO', $inspeccion['I'])
                            ->where('No_ACTA', $inspeccion['E'])
                            ->where('TIPO_TRABAJO', $inspeccion['G'])
                            ->exists();
                        if (!$existe) {
                            TblTempFallida::create([
                                'NOMBRE' => $inspeccion['A'],
                                'CC_OPERARIO' => $inspeccion['B'],
                                'MUNICIPIO' => $inspeccion['C'],
                                'FECHA' => $inspeccion['D'],
                                'No_ACTA' => $inspeccion['E'],
                                'TIPO_TRABAJO' => $inspeccion['G'],
                                'CONTRATO' => $inspeccion['H'],
                                'ORDEN_TRABAJO' => $inspeccion['I'],
                                'ORDEN_EXT' => $inspeccion['J'],
                                'CATEGORIA' => $inspeccion['K'],
                                'RESULTADO_CIERRE' => $inspeccion['M'],
                                'id_bitacora' => $bitacora->id,
                                'id_usuario' => $usuario->id,
                                'id_super' => $super ?? 1,
                            ]);
                        }
                    }
                }
            }

            foreach ($datos as $cc => $inspecciones) {

                foreach ($inspecciones as $inspeccion) {

                    $existe = TblTempContrato::where('CONTRATO', $inspeccion['H'])
                        ->where('ORDEN_TRABAJO', $inspeccion['I'])
                        ->where('No_ACTA', $inspeccion['E'])
                        ->where('TIPO_TRABAJO', $inspeccion['G'])
                        ->exists();

                    if (!$existe || $cierre_todos === '0') {


                        //Validacion para el campo de recintos, no permite valores inferiores a 4
                        $valorRecintos = 'NO';

                        //  Definimos los valores que también deben ser considerados como 'NO'.
                        $valoresInvalidos = ['1', '2', '3'];
                        $contrato_devolucion = TblDvInsp::where('CONTRATO', $inspeccion['H'])
                            ->where('GESTIONADO','=','0')->exists();
                        $g_devolucion_val = $contrato_devolucion ? 1 : 0;
                        //  Verificamos si la clave 'S' existe y si su valor no está en la lista de inválidos.
                        if (isset($inspeccion['S']) && !in_array($inspeccion['S'], $valoresInvalidos)) {
                            // Si la condición es verdadera, usamos el valor original.
                            $valorRecintos = $inspeccion['S'];
                        }

                        TblTempContrato::create([
                            'NOMBRE' => $inspeccion['A'],
                            'CC_OPERARIO' => $inspeccion['B'],
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
                            '4_RECINTOS' => $valorRecintos,
                            'VENCE' => $inspeccion['Q'],
                            'PERIODO_GRACIA' => $inspeccion['R'],
                            'id_bitacora' => $bitacora->id,
                            'id_usuario' => $usuario->id,
                            'id_super' => $super ?? 1,
                            'G_DEVOLUCION' => $g_devolucion_val,
                        ]);
                    }
                }
            }
            $datosDB = TblTempContrato::where('id_bitacora', $bitacora->id)->get();
        } catch (\Exception $e) {
            throw $e;
        }
        return $datosDB;
    }

    public function Restaurar($id_bitacora)
    {
        try {
            $archivo = TblBitacoraArchivo::select('finished')->where('id', $id_bitacora)->first();

            if ($archivo->finished === 1) {
                return null;
            }
        } catch (\Exception $e) {
            return redirect()->route('bitacora')->with('error', 'Error en el proceso');
        }
        try {
            $super = TblTempContrato::select('id_super')->where('id_bitacora', $id_bitacora)->first();
            $id_super = $super->id_super;
            $inspectores = TblInspCali::where('SUPERVISOR', $id_super)
                ->where('state', 1)
                ->orderBy('apellidos', 'asc')
                ->get();
        }catch (\Exception $e) {
            return redirect()->route('bitacora')->with('error', 'Error en el proceso, no se puede identificar el supervisor
            por favor vuelve a generar la bitácora');
        }
        foreach ($inspectores as $inspector) {
            $nombres[] = $inspector->apellidos . ' ' . $inspector->nombres;
            $cedulas[] = $inspector->cedula;
            $ids[$inspector->cedula] = $inspector->id;
        }

        session(['ids_inspectores' => $ids]);

        $municipios = TblLocalidadesMunicipio::all();
        $response = TblTempContrato::where('id_bitacora', $id_bitacora)->get();
        $causales = TblBitacorasCausal::all();

        return view('bitacoras.tabla', compact('response', 'nombres', 'municipios', 'causales', 'id_super', 'inspectores', 'cedulas'));
    }

    public function Borrar($id_bitacora)
    {
        try {
            $contratos = TblTempContrato::where('id_bitacora', $id_bitacora)->get();
            $fallidas = TblTempFallida::where('id_bitacora', $id_bitacora)->get();
            foreach ($contratos as $contrato) {
                $contrato->delete();
            }
            foreach ($fallidas as $fallida) {
                $fallida->delete();
            }
            $archivo = TblBitacoraArchivo::where('id', $id_bitacora)->first();
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
            $contrato = TblTempContrato::find($id);

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

            $contrato = new TblTempContrato();
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
