<?php

namespace App\Services\Programacion;

use App\Models\tbl_insp_cali;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

/**
 * Reparto del agendamiento a los supervisores.
 *
 * Cada supervisor recibe un PDF sólo con las visitas de sus técnicos, y aparte
 * va un Excel con todo. Los dos se empaquetan en un ZIP porque es lo que se
 * descarga de una vez y se reenvía.
 */
class ReporteSupervisorService
{
    /** Cabeceras del Excel, en el orden en que salen las columnas de la rejilla. */
    private const CABECERAS = [
        'Contrato', 'Tipo de trabajo', 'Fecha', 'Celular', 'Nombre de Usuario',
        'Orden de trabajo', 'Direccion', 'Barrio', 'Ciudad', 'Activa', 'Suspendida',
        'Categoria', 'Fecha de agendamiento', 'Observaciones', 'Quien programo',
        'Tecnico', 'Jornada',
    ];

    /** Hasta dónde llegan los bordes y el relleno de la cabecera. */
    private const ULTIMA_COLUMNA = 'R';

    /** Posición del técnico en la fila que manda la rejilla. */
    private const COL_TECNICO = 16;

    /**
     * Genera el ZIP y devuelve su nombre de archivo.
     *
     * @param array $data Filas de la rejilla, con el id en la posición 0.
     * @throws PlantillaInvalida Cuando una fila no tiene técnico.
     */
    public function generar(array $data, ?string $fechaInicio, ?string $fechaFin): string
    {
        $this->exigirTecnicos($data);

        $carpeta = storage_path('app/uploads/');

        if (! File::exists($carpeta)) {
            File::makeDirectory($carpeta, 0755, true);
        }

        $archivos = [];

        foreach ($this->agruparPorSupervisor($data) as $grupo) {
            $archivos[] = $this->pdfDeSupervisor($grupo['supervisor'], $grupo['registros'], $carpeta);
        }

        $archivos[] = $this->excelTotal($data, $carpeta, $fechaInicio, $fechaFin);

        $nombreZip = 'AGENDAMIENTO_' . $this->sufijoDeFechas($fechaInicio, $fechaFin) . '.zip';
        $this->empaquetar($archivos, $carpeta . $nombreZip);

        // Los sueltos ya están dentro del ZIP; sólo se conserva el paquete.
        foreach ($archivos as $archivo) {
            if (file_exists($archivo)) {
                unlink($archivo);
            }
        }

        return $nombreZip;
    }

    /**
     * Sin técnico no se puede repartir la visita a ningún supervisor.
     *
     * @throws PlantillaInvalida
     */
    private function exigirTecnicos(array $data): void
    {
        foreach ($data as $indice => $fila) {
            $tecnico = $fila[self::COL_TECNICO] ?? null;

            if ($tecnico === '' || $tecnico === null) {
                throw new PlantillaInvalida('Programación sin tecnico, revise la fila ' . ($indice + 1));
            }
        }
    }

    /**
     * Reparte las filas entre los supervisores de sus técnicos.
     *
     * El técnico llega como "12. APELLIDOS NOMBRES"; del número se saca el
     * inspector y de ahí su supervisor. Una fila cuyo técnico no exista o no
     * tenga supervisor se queda fuera de los PDF —pero sigue en el Excel—.
     *
     * @return list<array{supervisor: object, registros: list<array>}>
     */
    private function agruparPorSupervisor(array $data): array
    {
        $grupos = [];

        foreach ($data as $fila) {
            $inspector = tbl_insp_cali::find((int) strtok($fila[self::COL_TECNICO], '.'));

            if (! $inspector || ! $inspector->supervisor) {
                continue;
            }

            $id = $inspector->supervisor->id;

            $grupos[$id] ??= [
                'supervisor' => (object) ['id' => $id, 'nombre' => $inspector->supervisor->name],
                'registros'  => [],
            ];

            $grupos[$id]['registros'][] = $fila;
        }

        return array_values($grupos);
    }

    /** PDF apaisado con las visitas de un supervisor. */
    private function pdfDeSupervisor(object $supervisor, array $registros, string $destino): string
    {
        $html = view('reportes.supervisor_pdf', compact('supervisor', 'registros'))->render();

        $nombre = preg_replace('/[^A-Za-z0-9_\-]/', '_', $supervisor->nombre)
            ?: 'supervisor_' . $supervisor->id;

        // time() evita que dos supervisores con el mismo nombre se pisen.
        $ruta = $destino . 'reporte_' . $nombre . '_' . time() . '.pdf';

        $pdf = new Mpdf(['orientation' => 'L']);
        $pdf->WriteHTML($html);
        $pdf->Output($ruta, \Mpdf\Output\Destination::FILE);

        return $ruta;
    }

    /** Excel con todas las filas, sin repartir. */
    private function excelTotal(array $data, string $destino, ?string $fechaInicio, ?string $fechaFin): string
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();

        $hoja->fromArray(self::CABECERAS, null, 'A1');
        $hoja->getStyle('A1:' . self::ULTIMA_COLUMNA . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
        ]);

        $numeroFila = 2;
        foreach ($data as $fila) {
            // Se descarta el id, que es la primera columna de la rejilla.
            $hoja->fromArray(array_slice($fila, 1), null, 'A' . $numeroFila++);
        }

        $hoja->getStyle('A1:' . self::ULTIMA_COLUMNA . (count($data) + 1))->applyFromArray([
            'borders' => ['allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['argb' => 'FF000000'],
            ]],
        ]);

        for ($col = 'A'; $col <= self::ULTIMA_COLUMNA; $col++) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }

        $ruta = $destino . 'Agendamiento_total ' . $this->sufijoDeFechas($fechaInicio, $fechaFin) . '.xlsx';
        (new Xlsx($libro))->save($ruta);

        return $ruta;
    }

    /**
     * Mete los archivos en un ZIP.
     *
     * Si alguno no está se anota y se sigue: es preferible entregar el paquete
     * incompleto que no entregar nada.
     */
    private function empaquetar(array $archivos, string $rutaZip): string
    {
        $zip = new ZipArchive();

        if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('No se pudo crear el archivo ZIP en: ' . $rutaZip);
        }

        foreach ($archivos as $archivo) {
            if (file_exists($archivo) && is_readable($archivo)) {
                $zip->addFile($archivo, basename($archivo));
            } else {
                Log::error('El archivo no pudo ser agregado al ZIP porque no existe: ' . $archivo);
            }
        }

        $zip->close();

        return $rutaZip;
    }

    /** "2026_09_10" o "2026_09_10_2026_09_15", según haya rango. */
    private function sufijoDeFechas(?string $inicio, ?string $fin): string
    {
        return ($inicio ? str_replace('-', '_', $inicio) : '')
            . ($fin ? '_' . str_replace('-', '_', $fin) : '');
    }
}
