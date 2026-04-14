<?php

namespace App\Services\PQRS;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class CoordinacionPQRSExportService
{
    /**
     * Procesa la creación del Excel con estilos y genera la URL firmada.
     */
    public static function generarExcelSupervisor($quejas, $nombreSupervisor)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Encabezados
        $headers = ['CONTRATO', 'ASIGNADO', 'OBSERVACIÓN SOLICITUD', 'INSTRUCCIONES CAMPO','OBSERVACION SUPERVISOR'];
        $sheet->fromArray($headers, NULL, 'A1');

        // 2. Llenar Datos
        $fila = 2;
        foreach ($quejas as $item) {
            $sheet->setCellValue('A' . $fila, $item->CONTRATO);
            $sheet->setCellValue('B' . $fila, $item->ASIGNADO);
            $sheet->setCellValue('C' . $fila, $item->OBSERVACION_SOLICITUD);
            $sheet->setCellValue('D' . $fila, $item->INSTRUCCIONES_CAMPO);
            $sheet->setCellValue('E' . $fila, $item->OBSERVACION_SUPERVISOR);
            $fila++;
        }
        $ultimaFila = $fila - 1;

        // 3. Aplicar Estilos
        self::aplicarEstilos($sheet, $ultimaFila);

        // 4. Guardar archivo físicamente
        $nombreArchivo = 'Export_' . str_replace(' ', '_', $nombreSupervisor) . '_' . time() . '.xlsx';

        if (!Storage::exists('uploads')) {
            Storage::makeDirectory('uploads');
        }

        $rutaAbsoluta = storage_path('app/uploads/' . $nombreArchivo);
        $writer = new Xlsx($spreadsheet);
        $writer->save($rutaAbsoluta);

        // 5. Generar URL Firmada
        return URL::temporarySignedRoute(
            'descargar.archivo',
            now()->addMinutes(10),
            ['file' => $nombreArchivo]
        );
    }

    /**
     * Aplica colores, bordes y auto-ajuste.
     */
    private static function aplicarEstilos($sheet, $ultimaFila)
    {
        // Estilo Cabecera: Azul con texto blanco
        $styleHeader = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '007BFF'],
            ],
        ];

        // Estilo Bordes
        $styleBorders = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        // Aplicar a celdas
        $sheet->getStyle('A1:E1')->applyFromArray($styleHeader);
        $sheet->getStyle('A1:E' . $ultimaFila)->applyFromArray($styleBorders);

        // --- CONFIGURACIÓN DE ANCHOS DE COLUMNA ---

        // Columna A: CONTRATO (Ancho ajustado al encabezado)
        $sheet->getColumnDimension('A')->setWidth(15);

        // Columna B: RESPONSABLE (Ancho medio)
        $sheet->getColumnDimension('B')->setWidth(25);

        // Columna C: OBSERVACIÓN SOLICITUD (Ancho fijo razonable + Ajuste de texto)
        $sheet->getColumnDimension('C')->setWidth(50);
        $sheet->getStyle('C2:C' . $ultimaFila)->getAlignment()->setWrapText(true);

        // Columna D: INSTRUCCIONES CAMPO (Ancho fijo razonable + Ajuste de texto)
        $sheet->getColumnDimension('D')->setWidth(50);
        $sheet->getStyle('D2:D' . $ultimaFila)->getAlignment()->setWrapText(true);

        // Alineación vertical superior para todas las celdas (mejor legibilidad con textos largos)
        $sheet->getStyle('A1:D' . $ultimaFila)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }

    public static function generarExcelDesdeMatriz($datosTabla)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Definir los encabezados (Los mismos que tienes en Handsontable)
        $headers = [
            'NÚMERO ORDEN', 'CONTRATO', 'CÉDULA', 'NOMBRE', 'DEPARTAMENTO',
            'LOCALIDAD', 'BARRIO', 'DIRECCIÓN', 'CATEGORÍA',
            'COD UNIDAD OPERATIVA', 'TIPO TRABAJO', 'FECHA ASIGNACIÓN',
            'OBSERVACIÓN SOLICITUD', 'FECHA CIERRE ÚLTIMA', 'OBSERVACIÓN CIERRE ÚLTIMA',
            'TIPO TRABAJO CIERRE ÚLTIMA', 'CAUSAL CIERRE ÚLTIMA', 'FECHA ASIGNACIÓN ÚLTIMA',
            'OBSERVACIÓN ASIGNACIÓN ÚLTIMA', 'GESTIÓN ASIGNACIÓN ÚLTIMA', 'TIPO TRABAJO ASIGNACIÓN ÚLTIMA',
            'RESPONSABLE', 'ASIGNADO','SUPERVISOR' ,'FECHA ASIGNADO','RECEPCIÓN',
            'FECHA RECEPCIÓN', 'OBSERVACIÓN GESTIÓN', 'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA',
            'FECHA LEGALIZACIÓN', 'CAUSAL LEGALIZACIÓN', 'OBSERVACIÓN LEGALIZACIÓN'
        ];

        $sheet->fromArray($headers, NULL, 'A1');

        // 2. Volcar todos los datos recibidos desde la fila 2
        // Como $datosTabla ya es un array de arrays, fromArray lo inserta todo de golpe (muy eficiente)
        $sheet->fromArray($datosTabla, NULL, 'A2');

        $ultimaFila = count($datosTabla) + 1;
        $highestColumn = $sheet->getHighestColumn();

        // 3. Aplicar Estilos (Cabecera y Bordes)
        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '007BFF']],
        ];
        $styleBorders = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];

        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray($styleHeader);
        $sheet->getStyle('A1:' . $highestColumn . $ultimaFila)->applyFromArray($styleBorders);

        // 4. Ajuste Inteligente de Columnas
        $columnIndex = 1;
        while ($columnIndex <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn)) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $headerValue = $sheet->getCell($colLetter . '1')->getValue();

            // Limitamos el ancho de las columnas de observación
            if (strpos(strtoupper($headerValue), 'OBSERVACI') !== false) {
                $sheet->getColumnDimension($colLetter)->setWidth(50);
                $sheet->getStyle($colLetter . '2:' . $colLetter . $ultimaFila)->getAlignment()->setWrapText(true);
            } else {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
            $columnIndex++;
        }

        $sheet->getStyle('A1:' . $highestColumn . $ultimaFila)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        // 5. Guardar el archivo físicamente
        $nombreArchivo = 'Historico_Quejas_' . time() . '.xlsx';

        if (!Storage::exists('uploads')) {
            Storage::makeDirectory('uploads');
        }

        $rutaAbsoluta = storage_path('app/uploads/' . $nombreArchivo);
        $writer = new Xlsx($spreadsheet);
        $writer->save($rutaAbsoluta);

        // 6. Retornar URL firmada
        return URL::temporarySignedRoute(
            'descargar.archivo',
            now()->addMinutes(10),
            ['file' => $nombreArchivo]
        );
    }
}
