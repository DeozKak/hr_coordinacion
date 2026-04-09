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
        $headers = ['CONTRATO', 'ASIGNADO', 'OBSERVACIÓN SOLICITUD', 'INSTRUCCIONES CAMPO'];
        $sheet->fromArray($headers, NULL, 'A1');

        // 2. Llenar Datos
        $fila = 2;
        foreach ($quejas as $item) {
            $sheet->setCellValue('A' . $fila, $item->CONTRATO);
            $sheet->setCellValue('B' . $fila, $item->ASIGNADO);
            $sheet->setCellValue('C' . $fila, $item->OBSERVACION_SOLICITUD);
            $sheet->setCellValue('D' . $fila, $item->INSTRUCCIONES_CAMPO);
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
        $sheet->getStyle('A1:D1')->applyFromArray($styleHeader);
        $sheet->getStyle('A1:D' . $ultimaFila)->applyFromArray($styleBorders);

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
}
