<?php

namespace App\Http\Controllers\Bitacoras;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Log;
class BitacoraDiariaController extends Controller
{
    public function RecepcionBitacoraDiraria(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:xls'
        ], [
            'archivo.required' => 'El archivo es requerido',
            'archivo.file' => 'El archivo debe ser un archivo',
            'archivo.mimes' => 'El archivo debe ser un archivo excel xlsx',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }


        try {
            $spreadsheet = IOFactory::load($request->file('archivo'));

            if(!$this->validarArchivo($spreadsheet)){
                return response()->json(['error' => 'El archivo no cumple con los requerimientos'], 422);
            }

             $respuesta = $this->CrearInforme($spreadsheet);
            dd($respuesta);
            //return response()->json(['success' => 'Archivo recibido con exito'], 200);
        } catch (\Exception $e) {
            dd($e);
            Log::error($e);
            return response()->json(['error' => 'Error al Recibir el archivo',$e], 500);
        }

    }

    private function validarArchivo($spreadsheet)
    {

        $sheet = $spreadsheet->getActiveSheet();
        $validaciones = [
            'A' => 'INSPECTOR ',
            'B' => 'CC OPERARIO ',
            'C' => 'MUNICIPIO ',
            'D' => 'FECHA',
            'E' => 'N° ACTA ',
            'G' => 'TIPO DE TRABAJO',
            'H' => 'CONTRATO',
            'I' => 'ORDEN DE TRABAJO',
            'J' => 'ORDEN DE TRABAJO EXT',
            'K' => 'CATEGORIA ',
            'M' => 'RESULTADO DE LA INSPECCION',
            'N' => 'HORA INICIO ',
            'O' => 'HORA FINAL ',
            'Q' => 'VENCE',
            'R' => 'Aplica periodo de gracia',
        ];
        $row = 1;

        foreach ($validaciones as $columna => $valorEsperado) {
            $valor = $sheet->getCell($columna . $row)->getValue();
            $indicador = $valor === $valorEsperado;

            // Si un valor no es válido, puedes lanzar un error o manejar la falla aquí
            if (!$indicador) {
                return false;
            }
        }

        return true;
    }

    private function CrearInforme($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();

        $columnas = ['A', 'B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'M', 'N', 'O', 'Q', 'R'];
        $informe = new Spreadsheet();
        $hoja_informe = $informe->getActiveSheet();
        $encabezados = [
            'INSPECTOR',
            'CC OPERARIO',
            'MUNICIPIO',
            'FECHA',
            'N° ACTA',
            'TIPO DE TRABAJO',
            'CONTRATO',
            'ORDEN DE TRABAJO',
            'ORDEN EXT',
            'CATEGORIA',
            'RESULTADO CIERRE',
            'HORA INICIO',
            'HORA FINAL',
            'DURACION',
            'VENCE',
            'PERIODO DE GRACIA'
        ];
        $procesadores = [
            'D' => fn($valor) => date('Y-m-d', strtotime($valor)), // Formatear fecha
            'N' => fn($inicio) => $inicio, // Hora inicio sin procesar
            'O' => fn($final) => $final, // Hora final sin procesar
            'P' => function ($inicio, $final) {
                // Calcular duración entre hora inicio y final
                $inicio_dt = new \DateTime($inicio);
                $final_dt = new \DateTime($final);
                $diferencia = $inicio_dt->diff($final_dt);
                return $diferencia->format('%H:%I:%S');
            },
            'Q' => fn($vence) => $vence // Procesar "vence", si es necesario
        ];

        try{
            $fila_informe = 2;
        foreach ($sheet->getRowIterator() as $row) {
            if ($row->getRowIndex() === 1){
                $hoja_informe->fromArray($encabezados, null, 'A1');
                continue;
            }
            $contrato = $sheet->getCell('H' . $row->getRowIndex())->getValue();
            $vence = $sheet->getCell('Q' . $row->getRowIndex())->getValue();
            $cierre = ltrim($sheet->getCell('M' . $row->getRowIndex())->getValue(), '.');

            if(
                strpos($contrato, ":") === 0 &&
                in_array($cierre, ["CERTIFICADA", "CERTIFICADA CON NOVEDADES", "INSPECCIONADA CON DEFECTO CRITICO VALLE", "INSPECCIONADA CON DEFECTO NO CRITICO VALLE"])
            ){
                $fila_datos = [];

                foreach ($columnas as $columna) {
                    $valor = $sheet->getCell($columna . $row->getRowIndex())->getValue();
                    $fila_datos[] = $valor;
                }

                $hoja_informe->fromArray($fila_datos, null, 'A' .$fila_informe);
                $fila_informe++;
            }
        }

        $writer = IOFactory::createWriter($informe, 'Xlsx');
        $nombre_archivo = 'Bitacora Valle' . date('Y-m-d') . 'TODOS.xlsx';
        $writer->save(storage_path('app/uploads/') . $nombre_archivo);
        return response()->json(['url' => '../storage/app/uploads/' . $nombre_archivo],200);
        }catch (\Exception $e){
            dd($e);
            Log::error($e);
            return response()->json(['error' => 'Error al crear el informe', $e], 500);

        }
    }
}
