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

            return response()->json(['success' => 'Archivo recibido con exito',
                'url' => $respuesta], 200);
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
            'VENCE',
            'PERIODO DE GRACIA'
        ];
        $procesadores = [
            'D' => function($valor) { $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor);
                return $fecha->format('Y-m-d');}, // Formatear fecha tipo excel a fecha
            'N' => fn($inicio) => $inicio, // Hora inicio sin procesar
            'M' => fn($cierre) => ltrim($cierre, '.'),
            'O' => fn($final) => $final, // Hora final sin procesar
            'Q' => function ($vence) {
                $venceDate = \DateTime::createFromFormat('d/m/Y', $vence); // convertir a fecha
                return ($venceDate && $venceDate->format('Y') == date('Y') && $venceDate->format('m') == date('m'))
                    ? "60 meses"
                    : "";
            },
            'R' => function ($periodo) {if($periodo === 'Si'){return 'SI';}else{return 'NO';} },

        ];

        try{
            $fila_informe = 2;
        foreach ($sheet->getRowIterator() as $row) {
            if ($row->getRowIndex() === 1){
                $hoja_informe->fromArray($encabezados, null, 'A1');
                $hoja_informe->getStyle('A1:O1')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0096FF'], // Azul
                    ],
                ]);

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

                    // Aplica procesadores
                    if (isset($procesadores[$columna])) {
                            $valor = $procesadores[$columna]($valor);
                    }

                    $fila_datos[] = $valor;
                }

                $hoja_informe->fromArray($fila_datos, null, 'A' . $fila_informe);

                $this->EstilosInforme($hoja_informe,$fila_informe,$sheet,$row);;

                $fila_informe++;

            }

        }

            // Aplicar bordes a todas las celdas con contenido
            $hoja_informe->getStyle('A1:O' . ($fila_informe - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'], // Negro
                    ],
                ],
            ]);

            // Ajustar el ancho de todas las columnas al contenido
            foreach ($columnas as $columna) {
                $hoja_informe->getColumnDimension($columna)->setAutoSize(true);
            }


            $writer = IOFactory::createWriter($informe, 'Xlsx');
        $nombre_archivo = 'Bitacora Valle ' . date('Y-m-d') . ' TODOS.xlsx';
        $writer->save(storage_path('app/uploads/') . $nombre_archivo);
        return '../storage/app/uploads/' . $nombre_archivo;
        }catch (\Exception $e){

            Log::error($e);
            return response()->json(['error' => 'Error al crear el informe', $e], 500);

        }
    }

    private function EstilosInforme($hoja_destino,$fila_informe, $hoja_origen,$fila_origen){
        // Estilo columna G (verde)
        $hoja_destino->getStyle('G' . $fila_informe)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '92D050'], // VERDE
            ],
        ]);

        // Estilo columna J (naranja si es COMERCIAL)
        $categoria = $hoja_origen->getCell('K' . $fila_origen->getRowIndex())->getValue();
        if (trim($categoria) === 'COMERCIAL') {
            $hoja_destino->getStyle('J' . $fila_informe)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FF8000'], // NARANJA
                ],
            ]);
        }

        //Estilo columna N vence (Marca Naranja si es 60 meses)
        if($hoja_destino->getCell('N' . $fila_informe)->getValue() === '60 meses'){

            $hoja_destino->getStyle('N' . $fila_informe)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FF8000'], // NARANJA
                ],
            ]);
        }

        if($hoja_destino->getCell('O' . $fila_informe)->getValue() === 'SI'){
            $hoja_destino->getStyle('O' . $fila_informe)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FF8000'], // NARANJA
                ],
            ]);
        }

    }
}
