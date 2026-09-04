<?php

namespace App\Services\Programacion;

use App\Models\tbl_insp_cali;
use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use RuntimeException;

/**
 * Plantilla CSV para cargar la programación en GDW.
 *
 * GDW espera un separador de punto y coma, sin comillas, y sus propios códigos
 * de tipo de tarea: nada de esto se parece a lo que maneja la aplicación, así
 * que la traducción vive aquí y no repartida por el controlador.
 */
class PlantillaGdwService
{
    /** Nuestro tipo de trabajo => id de tarea en GDW. */
    private const TAREAS_GDW = [
        '10444' => 37166,
        '12161' => 35699,
        '12162' => 35698,
        '12163' => 35701,
        '12164' => 35700,
        '12166' => 37179,
    ];

    /** Lo que GDW pone cuando el tipo no le consta. */
    private const TAREA_DESCONOCIDA = 'TIPO TAREA NO EXISTE';

    /** La visita ocupa el día entero; GDW quiere principio y fin. */
    private const HORA_INICIO = '06:59:00 a.m.';
    private const HORA_FINAL  = '05:59:00 p.m.';

    private const GRUPO = 'INSP-VALLE';
    private const PRIORIDAD = '1934';

    private const ENCABEZADOS = [
        'Nro contrato', 'Direccion', 'fecha Visita', 'fecha Fin programado',
        'Grupo', 'Nro Operario', 'Id Tipo de Tarea', 'Id Prioridad', 'Detalle',
        'Nro de tarea interno', 'Codigo del bien (opcional)',
    ];

    /**
     * Genera el CSV y devuelve su nombre de archivo.
     *
     * @param array $data Filas de la tabla, indexadas de 1 a 17.
     * @throws PlantillaInvalida Cuando una fila no se puede exportar.
     */
    public function generar(array $data): string
    {
        $filas = $this->filas($data);

        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->fromArray(self::ENCABEZADOS, null, 'A1');
        $hoja->fromArray($filas, null, 'A2');

        $escritor = new Csv($libro);
        $escritor->setDelimiter(';');   // lo que espera GDW
        $escritor->setEnclosure('');    // sin comillas: las rechaza

        $nombre = 'Plantilla Programacion GDW ' . date('Y-m-d H-i-s') . '.csv';
        $escritor->save(storage_path('app/uploads/') . $nombre);

        return $nombre;
    }

    /**
     * Traduce las filas de la tabla al formato de GDW.
     *
     * @throws PlantillaInvalida
     */
    private function filas(array $data): array
    {
        $filas = [];

        foreach ($data as $indice => $item) {
            // Sin orden de trabajo no hay nada que cargar en GDW.
            if ($item[6] === 'N/A' || $item[6] === null) {
                continue;
            }

            $numeroFila = $indice + 1;

            if ($item[16] === '' || $item[16] === null) {
                throw new PlantillaInvalida("Programación sin tecnico, revise la fila {$numeroFila}");
            }

            $filas[] = [
                ':' . $item[1],
                $item[7],
                $this->fechaGdw($item[13], self::HORA_INICIO),
                $this->fechaGdw($item[13], self::HORA_FINAL),
                self::GRUPO,
                $this->cedulaDelTecnico($item[16], $numeroFila),
                self::TAREAS_GDW[$item[2]] ?? self::TAREA_DESCONOCIDA,
                self::PRIORIDAD,
                'TEL: ' . $item[4] . ' Nombre Usuario: ' . $item[5] . ' ' . $item[14],
                '', '', '',
            ];
        }

        return $filas;
    }

    /**
     * Cédula del inspector a partir del "12. APELLIDOS NOMBRES" de la celda.
     *
     * @throws PlantillaInvalida
     */
    private function cedulaDelTecnico(string $tecnico, int $numeroFila): string
    {
        preg_match('/^(\d+)\./', $tecnico, $partes);

        $inspector = isset($partes[1])
            ? tbl_insp_cali::select('cedula')->where('id', $partes[1])->first()
            : null;

        if ($inspector === null) {
            throw new PlantillaInvalida("No se encuentra id técnico, revise fila {$numeroFila}");
        }

        return $inspector->cedula;
    }

    /** Fecha y hora en el formato que lee GDW. */
    private function fechaGdw(string $fecha, string $hora): string
    {
        return DateTime::createFromFormat('Y-m-d h:i:s A', $fecha . ' ' . $hora)
            ->format('d/m/Y h:i:s a');
    }
}
