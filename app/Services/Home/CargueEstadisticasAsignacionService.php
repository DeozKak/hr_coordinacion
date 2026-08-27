<?php

namespace App\Services\Home;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\ReaderAbstract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CargueEstadisticasAsignacionService
{
    /** Filas por bloque de inserción, para no reventar el límite de placeholders */
    private const TAMANO_BLOQUE = 500;

    /**
     * Carga los dos archivos del día: asignaciones (foto del día) y cerradas (histórico).
     *
     * @param UploadedFile|null $archivoAsignacion Excel/CSV de OT abiertas.
     * @param UploadedFile|null $archivoCerradas Excel/CSV de OT cerradas.
     * @return array cantidad de filas procesadas por cada archivo.
     */
    public function procesar(?UploadedFile $archivoAsignacion, ?UploadedFile $archivoCerradas): array
    {
        // Los archivos se leen fila por fila, así que la memoria ya no crece con el tamaño
        // del Excel. El tope se deja solo como margen para el control de duplicados.
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');

        return [
            'asignaciones' => $archivoAsignacion ? $this->importarAsignaciones($archivoAsignacion) : 0,
            'cerradas'     => $archivoCerradas ? $this->importarCerradas($archivoCerradas) : 0,
        ];
    }

    /**
     * Foto del día: se vacía la tabla y se inserta todo el archivo.
     *
     * @return int filas insertadas.
     */
    public function importarAsignaciones(UploadedFile $archivo): int
    {
        DB::table('tbl_asignaciones')->truncate();

        $insertadas = 0;
        $bloque = [];
        // Una misma orden puede venir repetida en el Excel: se conserva solo la primera.
        // Guardamos únicamente la llave, no la fila, para no acumular el archivo en memoria.
        $ordenesVistas = [];

        foreach ($this->filas($archivo) as $fila) {
            $orden = $fila['numero_orden'] ?? null;

            if (empty($orden) || isset($ordenesVistas[$orden])) {
                continue;
            }

            $ordenesVistas[$orden] = true;
            $bloque[] = $this->mapearAsignacion($fila);

            if (count($bloque) >= self::TAMANO_BLOQUE) {
                DB::table('tbl_asignaciones')->insert($bloque);
                $insertadas += count($bloque);
                $bloque = [];
            }
        }

        if (!empty($bloque)) {
            DB::table('tbl_asignaciones')->insert($bloque);
            $insertadas += count($bloque);
        }

        return $insertadas;
    }

    /**
     * Histórico: se acumula sobre lo existente, actualizando las órdenes ya cargadas.
     *
     * @return int filas enviadas al upsert.
     */
    public function importarCerradas(UploadedFile $archivo): int
    {
        $columnasActualizables = array_values(array_diff(
            array_keys($this->mapearCerrada([])),
            ['NUMERO_ORDEN']
        ));

        $procesadas = 0;
        // Indexado por número de orden para descartar duplicados dentro del mismo bloque
        $bloque = [];

        foreach ($this->filas($archivo) as $fila) {
            $orden = $fila['numero_orden'] ?? null;

            if (empty($orden) || isset($bloque[$orden])) {
                continue;
            }

            $bloque[$orden] = $this->mapearCerrada($fila);

            if (count($bloque) >= self::TAMANO_BLOQUE) {
                $procesadas += $this->guardarCerradas($bloque, $columnasActualizables);
                $bloque = [];
            }
        }

        $procesadas += $this->guardarCerradas($bloque, $columnasActualizables);

        return $procesadas;
    }

    /**
     * @param array<string, array<string, mixed>> $bloque
     * @param array<int, string> $columnasActualizables
     * @return int filas enviadas al upsert.
     */
    private function guardarCerradas(array $bloque, array $columnasActualizables): int
    {
        if (empty($bloque)) {
            return 0;
        }

        DB::table('tbl_cerradas')->upsert(
            array_values($bloque),
            ['NUMERO_ORDEN'], // Llave principal
            $columnasActualizables
        );

        return count($bloque);
    }

    /**
     * Recorre la primera hoja y va entregando fila por fila como array asociativo,
     * usando los encabezados de la fila 1 (en minúsculas) como llaves.
     *
     * Es un generador: nunca se mantiene el archivo completo en memoria.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function filas(UploadedFile $archivo): \Generator
    {
        $ruta = $archivo->getRealPath();
        $extension = strtolower($archivo->getClientOriginalExtension());

        return match ($extension) {
            // Spout lee en streaming; es el camino normal para los archivos del día
            'csv'  => $this->filasEnStreaming(ReaderEntityFactory::createCSVReader(), $ruta),
            // Spout no soporta el formato viejo .xls, ahí toca PhpSpreadsheet
            'xls'  => $this->filasConPhpSpreadsheet($ruta),
            default => $this->filasEnStreaming(ReaderEntityFactory::createXLSXReader(), $ruta),
        };
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function filasEnStreaming(ReaderAbstract $reader, string $ruta): \Generator
    {
        // Devolvemos las fechas ya formateadas como texto, igual que hacía PhpSpreadsheet
        $reader->setShouldFormatDates(true);
        $reader->open($ruta);

        try {
            foreach ($reader->getSheetIterator() as $hoja) {
                $headers = null;

                foreach ($hoja->getRowIterator() as $fila) {
                    $valores = $fila->toArray();

                    if ($headers === null) {
                        $headers = $this->normalizarEncabezados($valores);
                        continue;
                    }

                    if ($registro = $this->combinar($headers, $valores)) {
                        yield $registro;
                    }
                }

                break; // Solo la primera hoja, como antes con getActiveSheet()
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * Camino alterno para .xls. Se lee solo el valor de las celdas (sin estilos),
     * que es lo que disparaba el agotamiento de memoria.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function filasConPhpSpreadsheet(string $ruta): \Generator
    {
        $reader = IOFactory::createReaderForFile($ruta);
        $reader->setReadDataOnly(true);

        $hoja = $reader->load($ruta)->getActiveSheet();
        $headers = null;

        foreach ($hoja->getRowIterator() as $fila) {
            $celdas = $fila->getCellIterator();
            $celdas->setIterateOnlyExistingCells(false);

            $valores = [];
            foreach ($celdas as $celda) {
                $valores[] = $celda->getValue();
            }

            if ($headers === null) {
                $headers = $this->normalizarEncabezados($valores);
                continue;
            }

            if ($registro = $this->combinar($headers, $valores)) {
                yield $registro;
            }
        }
    }

    /**
     * @param array<int, mixed> $valores
     * @return array<int, string>
     */
    private function normalizarEncabezados(array $valores): array
    {
        return array_map(fn ($h) => strtolower(trim((string) $h)), $valores);
    }

    /**
     * Arma la fila asociativa. Devuelve null si la fila viene vacía.
     *
     * @param array<int, string> $headers
     * @param array<int, mixed> $valores
     * @return array<string, mixed>|null
     */
    private function combinar(array $headers, array $valores): ?array
    {
        if (empty(array_filter($valores))) {
            return null;
        }

        $registro = [];
        foreach ($headers as $i => $header) {
            if ($header !== '') {
                $valor = $valores[$i] ?? null;
                $registro[$header] = $valor instanceof \DateTimeInterface
                    ? $valor->format('Y-m-d H:i:s')
                    : $valor;
            }
        }

        return $registro;
    }

    /**
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private function mapearAsignacion(array $fila): array
    {
        return [
            'NUMERO_ORDEN'          => $fila['numero_orden'] ?? null,
            'CONTRATO'              => $fila['contrato'] ?? null,
            'PRODUCTO'              => $fila['producto'] ?? null,
            'NUMERO_SOLICITUD'      => $fila['numero_solicitud'] ?? null,
            'TIPO_SOLICITUD'        => $fila['tipo_solicitud'] ?? null,
            'CEDULA'                => $fila['cedula'] ?? null,
            'NOMBRE'                => $fila['nombre'] ?? null,
            'DESC_DEPART'           => $fila['desc_depart'] ?? null,
            'DESC_LOCALIDAD'        => $fila['desc_localidad'] ?? null,
            'BARRIO'                => $fila['barrio'] ?? null,
            'DIRECCION'             => $fila['direccion'] ?? null,
            'CONSECUTIVO_RUTA'      => $fila['consecutivo_ruta'] ?? null,
            'TELEFONO'              => $fila['telefono'] ?? null,
            'MEDIDOR'               => $fila['medidor'] ?? null,
            'DESC_CATEGORIA'        => $fila['desc_categoria'] ?? null,
            'COD_UNIDAD_OPER'       => $fila['cod_unidad_oper'] ?? null,
            'ID_TIPO_TRABAJO'       => $fila['id_tipo_trabajo'] ?? null,
            'FECHA_ASIGNACION'      => $fila['fecha_asignacion'] ?? null,
            'OBSERVACION_SOLICITUD' => $fila['observacion_solicitud'] ?? null,
            'DESC_ESTADO_PRODUCTO'  => $fila['desc_estado_producto'] ?? null,
            'DESC_ESTADO_CORTE'     => $fila['desc_estado_corte'] ?? null,
            'ULTIMO_COMENTARIO'     => $fila['ultimo_comentario'] ?? null,
            'FECHA_ULTCERTI'        => $fila['fecha_ultcerti'] ?? null,
            'PLAZO_MAXIMO'          => $fila['plazo_maximo'] ?? null,
            'OIA_RECHAZO'           => $fila['oia_rechazo'] ?? null,
            'FECHA_RECHAZO'         => $fila['fecha_rechazo'] ?? null,
            'COMENTARIO_RECHAZO'    => $fila['comentario_rechazo'] ?? null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ];
    }

    /**
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private function mapearCerrada(array $fila): array
    {
        return [
            'NUMERO_ORDEN'       => $fila['numero_orden'] ?? null,
            'CONTRATO'           => $fila['contrato'] ?? null,
            'DESC_DEPART'        => $fila['desc_depart'] ?? null,
            'DESC_LOCALIDAD'     => $fila['desc_localidad'] ?? null,
            'DIRECCION'          => $fila['direccion'] ?? null,
            'CATE'               => $fila['cate'] ?? null,
            'NOM_CATE'           => $fila['nom_cate'] ?? null,
            'NOMBRE_TECNICO'     => $fila['nombre_tecnico'] ?? null,
            'ID_TIPO_TRABAJO'    => $fila['id_tipo_trabajo'] ?? null,
            'FECHA_ASIGNACION'   => $fila['fecha_asignacion'] ?? null,
            'FECHA_EJECUCION'    => $fila['fecha_ejecucion'] ?? null,
            'FECHA_LEGALIZACION' => $fila['fecha_legalizacion'] ?? null,
            'CAUSAL'             => $fila['causal'] ?? null,
            'DESCCAUSAL'         => $fila['desccausal'] ?? null,
            'ACTARP'             => $fila['actarp'] ?? null,
            'updated_at'         => now(),
        ];
    }
}
