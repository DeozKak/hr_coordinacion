<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\tbl_bitacora_contrato;
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

    public function guardar($spreadsheet, $nombres, $id_super, $inspectores)
    {

        $rutaArchivo = str_replace(".xls", " ", session('nom_archivo'));
        $rutaArchivoFinal = str_replace("4.08", "", $rutaArchivo);
        $nombreArchivo = $rutaArchivoFinal . ".xlsx";

        $usuario = Auth::user();
        try {
            $bitacora = new tbl_bitacora_archivo();
            $bitacora->id_usuario = $usuario->id;
            $bitacora->NOMBRE_ARCHIVO = $rutaArchivoFinal;
            $bitacora->ruta_archivo = 'storage/app/uploads/' . $nombreArchivo;
            $bitacora->save();
        } catch (\Exception $e) {
            return null;
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

                        foreach (['A','B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'M', 'N', 'O', 'Q'] as $columna) {
                            $valor = $sheet->getCell($columna . $row->getRowIndex())->getValue();

                            if ($columna === 'M') {
                                $valor = ltrim($sheet->getCell($columna . $row->getRowIndex())->getValue(), '.');
                            }
                            if ($columna === 'D' && is_numeric($valor)) {
                                $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor);
                                $valor = $fecha->format('d-m-y');
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

                    ]);
                }
            }
        $datosDB = tbl_temp_contrato::where('id_bitacora', $bitacora->id)->get();
        } catch (\Exception $e) {
            return null;
        }

        return $datosDB;
    }
}
