<?php

namespace App\Services\Bitacoras;

use App\Models\Bitacoras\TblBitacoraArchivo;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel de una bitácora ya guardada, armado cuando alguien lo pide.
 *
 * Antes el archivo se escribía dentro del guardado, con dos consecuencias: el
 * disco acumulaba un `.xlsx` por cada bitácora aunque nadie lo abriera, y un
 * fallo de PhpSpreadsheet tumbaba el guardado entero, porque la escritura
 * ocurría con la transacción abierta.
 *
 * Cada inspector tiene su hoja con todo lo suyo, inspecciones y devoluciones
 * mezcladas en orden de fecha; las devoluciones se reconocen por el número de
 * contrato en rojo. Al lado va el conteo por tipo de cierre, que deja fuera las
 * devoluciones porque no son un cierre.
 */
class ExcelBitacoraService
{
    /** Encabezado de las hojas, el mismo juego de columnas que el reporte. */
    private const COLUMNAS = [
        'INSPECTOR', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO',
        'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO CIERRE',
        'HORA INICIO', 'HORA FINAL', 'DURACION', '4 RECINTOS O MAS', 'VENCE', 'CAUSAL',
    ];

    /** Posición de CONTRATO dentro de COLUMNAS, en base 1. */
    private const COLUMNA_CONTRATO = 7;

    /** Los cierres que se cuentan, en el orden en que se quieren leer. */
    private const TIPOS_DE_CIERRE = [
        'CERTIFICADA',
        'CERTIFICADA CON NOVEDADES',
        'INSPECCIONADA CON DEFECTO CRITICO VALLE',
        'INSPECCIONADA CON DEFECTO NO CRITICO VALLE',
    ];

    private const ROJO = 'C00000';

    public function __construct(private readonly LecturaBitacoraService $lectura) {}

    public function construir(TblBitacoraArchivo $bitacora): Spreadsheet
    {
        $libro = new Spreadsheet;
        $libro->removeSheetByIndex(0);

        $porInspector = $this->lectura->filasPorInspector($bitacora->id);

        if ($porInspector->isEmpty()) {
            $this->hoja($libro, 'SIN INSPECCIONES', collect());
        }

        foreach ($porInspector as $inspector => $filas) {
            $this->hoja($libro, (string) $inspector, $filas);
        }

        $libro->setActiveSheetIndex(0);

        return $libro;
    }

    /**
     * Nombre con el que se ofrece la descarga.
     */
    public function nombreArchivo(TblBitacoraArchivo $bitacora): string
    {
        $base = trim((string) $bitacora->nombre_archivo) ?: "bitacora-{$bitacora->id}";

        /* Sólo se quitan los separadores de ruta y los caracteres de control:
           una lista blanca de A-Z se comía las eñes y las tildes de los
           nombres, que aquí son la norma. */
        $base = preg_replace('/[\/\\\\:*?"<>|]|[\x00-\x1F]/u', ' ', $base);

        return trim(preg_replace('/\s+/u', ' ', $base)).'.xlsx';
    }

    private function hoja(Spreadsheet $libro, string $inspector, Collection $filas): void
    {
        $hoja = $libro->createSheet();
        $hoja->setTitle($this->tituloValido($inspector));

        $this->encabezado($hoja);

        $n = 2;
        foreach ($filas as $f) {
            $hoja->fromArray([
                $f['nombre_completo'],
                $f['CC_OPERARIO'],
                $f['MUNICIPIO'],
                $f['FECHA'],
                $f['No_ACTA'],
                $f['TIPO_TRABAJO'],
                $f['CONTRATO'],
                $f['ORDEN_TRABAJO'],
                $f['ORDEN_EXT'],
                $f['CATEGORIA'],
                $f['RESULTADO_CIERRE'],
                $f['HORA_INICIO'],
                $f['HORA_FINAL'],
                $f['DURACION_INSP'],
                $f['4_RECINTOS'],
                $f['VENCE'],
                $f['CAUSAL'],
            ], null, 'A'.$n);

            if ($f['es_devolucion']) {
                $this->marcarDevolucion($hoja, $n);
            }

            $n++;
        }

        $this->rematar($hoja, $n - 1);
        $this->cuadroDeCierres($hoja, $filas, $n + 1);
    }

    /**
     * El contrato en rojo y en negrita: es lo único que distingue una
     * devolución del resto de filas de la hoja.
     */
    private function marcarDevolucion(Worksheet $hoja, int $fila): void
    {
        $celda = $hoja->getStyle([self::COLUMNA_CONTRATO, $fila, self::COLUMNA_CONTRATO, $fila]);

        $celda->getFont()->setBold(true)->getColor()->setRGB(self::ROJO);
        $celda->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE0E0');
    }

    /**
     * Conteo por tipo de cierre, debajo de la tabla.
     *
     * Las devoluciones quedan fuera a propósito: no son un cierre, son trabajo
     * que vuelve. Se cuentan aparte para que el total cuadre con lo que la
     * bitácora deja hecho.
     */
    private function cuadroDeCierres(Worksheet $hoja, Collection $filas, int $desde): void
    {
        $cerradas = $filas->where('es_devolucion', false);

        $hoja->setCellValue([1, $desde], 'RESUMEN POR TIPO DE CIERRE');
        $hoja->mergeCells([1, $desde, 2, $desde]);
        $estilo = $hoja->getStyle([1, $desde, 2, $desde]);
        $estilo->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E79');

        $n = $desde + 1;
        $total = 0;

        foreach (self::TIPOS_DE_CIERRE as $tipo) {
            $cuantas = $cerradas->where('RESULTADO_CIERRE', $tipo)->count();
            $total += $cuantas;

            $hoja->setCellValue([1, $n], $tipo);
            $hoja->setCellValue([2, $n], $cuantas);
            $n++;
        }

        /* Cualquier otro resultado que no esté en la lista, para que el cuadro
           no esconda filas: si aparece un cierre nuevo, se ve. */
        $otras = $cerradas->count() - $total;

        if ($otras > 0) {
            $hoja->setCellValue([1, $n], 'OTROS RESULTADOS');
            $hoja->setCellValue([2, $n], $otras);
            $n++;
        }

        $hoja->setCellValue([1, $n], 'TOTAL CERRADAS');
        $hoja->setCellValue([2, $n], $cerradas->count());
        $hoja->getStyle([1, $n, 2, $n])->getFont()->setBold(true);

        $devueltas = $filas->count() - $cerradas->count();
        $n++;
        $hoja->setCellValue([1, $n], 'DEVOLUCIONES');
        $hoja->setCellValue([2, $n], $devueltas);
        $hoja->getStyle([1, $n, 2, $n])->getFont()->setBold(true)->getColor()->setRGB(self::ROJO);

        $hoja->getStyle([1, $desde, 2, $n])->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function encabezado(Worksheet $hoja): void
    {
        $hoja->fromArray(self::COLUMNAS, null, 'A1');

        $estilo = $hoja->getStyle([1, 1, count(self::COLUMNAS), 1]);
        $estilo->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E79');
        $estilo->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $hoja->freezePane('A2');
    }

    private function rematar(Worksheet $hoja, int $ultimaFila): void
    {
        $columnas = count(self::COLUMNAS);

        $hoja->setAutoFilter('A1:'.$hoja->getCell([$columnas, 1])->getCoordinate());

        if ($ultimaFila > 1) {
            $hoja->getStyle([1, 1, $columnas, $ultimaFila])->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        for ($c = 1; $c <= $columnas; $c++) {
            $hoja->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
    }

    /**
     * Excel no admite más de 31 caracteres ni los signos \ / ? * [ ] : en el
     * nombre de una hoja, y los nombres de inspector los traen.
     */
    private function tituloValido(string $nombre): string
    {
        $limpio = preg_replace('/[\\\\\/?*\[\]:]/', ' ', trim($nombre));
        $limpio = trim(preg_replace('/\s+/', ' ', $limpio));

        return mb_substr($limpio !== '' ? $limpio : 'SIN NOMBRE', 0, 31);
    }
}
