<?php

namespace App\Http\Controllers\Bitacoras;

use App\Http\Controllers\Controller;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                $venceDate = \DateTime::createFromFormat('d/m/Y', $vence);

                if (!$venceDate) {
                    return "";
                }

                $hoy = new \DateTime(); // Fecha actual

                // Calculamos la diferencia al revés: HOY menos VENCIMIENTO
                $diferenciaAnios = (int)$hoy->format('Y') - (int)$venceDate->format('Y');
                $diferenciaMeses = (int)$hoy->format('m') - (int)$venceDate->format('m');

                // Esto dará negativo si es a futuro, y positivo si es en el pasado
                $mesesDiferencia = ($diferenciaAnios * 12) + $diferenciaMeses;

                // Se lo sumamos a tu base de 60
                $totalMeses = 60 + $mesesDiferencia;

                return $totalMeses . " meses";
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

                DB::beginTransaction();
                try{
                    // Verificación para evitar duplicados
                    $existeRegistro = DB::table('tbl_bitacora_diaria')->where([
                        ['CC_OPERARIO', '=', $fila_datos[1]],
                        ['MUNICIPIO', '=', $fila_datos[2]],
                        ['FECHA', '=', $fila_datos[3]],
                        ['ACTA', '=', $fila_datos[4]],
                        ['TIPO_TRABAJO', '=', $fila_datos[5]],
                        ['CONTRATO', '=', $fila_datos[6]],
                        ['ORDEN_TRABAJO', '=', $fila_datos[7]],
                        ['ORDEN_EXT', '=', $fila_datos[8]],
                        ['CATEGORIA', '=', $fila_datos[9]],
                        ['RESULTADO_CIERRE', '=', $fila_datos[10]],
                    ])->exists();

                    if (!$existeRegistro) {
                        DB::table('tbl_bitacora_diaria')->insert([
                            'CC_OPERARIO' => $fila_datos[1],
                            'MUNICIPIO' => $fila_datos[2],
                            'FECHA' => $fila_datos[3],
                            'ACTA' => $fila_datos[4],
                            'TIPO_TRABAJO' => $fila_datos[5],
                            'CONTRATO' => $fila_datos[6],
                            'ORDEN_TRABAJO' => $fila_datos[7],
                            'ORDEN_EXT' => $fila_datos[8],
                            'CATEGORIA' => $fila_datos[9],
                            'RESULTADO_CIERRE' => $fila_datos[10],
                        ]);
                        DB::commit();
                    }
                }catch (\Exception $e){
                    DB::rollBack();
                    Log::error($e);
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

        return url()->temporarySignedRoute(
            'descargar.archivo', // Usa la nueva ruta genérica
            now()->addMinutes(10), // Expiración en 10 minutos
            ['file' => $nombre_archivo] // Archivo como parámetro
        );

        }catch (\Exception $e){

            Log::error($e);
            return response()->json(['error' => 'Error al crear el informe', $e->getMessage()], 500);

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

        // Obtenemos el valor de la celda (ej: "60 meses", "61 meses", "59 meses")
        $valorCelda = $hoja_destino->getCell('N' . $fila_informe)->getValue();

        // Convertimos el texto a número. (int) convierte "61 meses" en 61.
        $cantidadMeses = (int) $valorCelda;

        // Estilo columna N vence (Marca Naranja si es 60 meses o más)
        if($cantidadMeses >= 60){
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

    public function VstCategoria(): \Illuminate\Contracts\View\View
    {

        $contratos_sin_categoria = tbl_bitacora_contrato::whereNotIn('TIPO_TRABAJO', [
            'FI-29 revisión periódica línea matriz',
            'FI-31 REVISIÓN NUEVA LINEA MATRIZ',
        ])->
        where('CATEGORIA',null)->orderBy('CC_OPERARIO')->get();

        return view('bitacoras.categorias',compact('contratos_sin_categoria'));
    }

    public function StoreCategoria(Request $request): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:tbl_bitacora_contratos,id',
            'categoria' => 'required|string'
        ], [
            'id.required' => 'El id es requerido',
            'id.integer' => 'El id debe ser una cadena de texto',
            'categoria.required' => 'La categoria es requerida',
            'categoria.string' => 'La categoria debe ser una cadena de texto'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        try{
        $registro = tbl_bitacora_contrato::find($request->id);

        DB::beginTransaction();

        $registro->CATEGORIA = $request->categoria;
        $registro->save();

        DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            log::error($e);
            return response()->json(['message' => 'Error al guardar los datos',
                'e' => $e->getMessage()], 500);
        }
        return response()->json(['success' => 'Registro actualizado correctamente'], 200);


    }
}
