<?php

namespace App\Services\Home;

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
        // Aumentamos tiempo y memoria temporalmente para que PhpSpreadsheet trabaje tranquilo
        ini_set('max_execution_time', 300);
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

        // Una misma orden puede venir repetida en el Excel: se conserva solo la primera
        $datos = collect($this->leerFilas($archivo))
            ->filter(fn (array $fila) => !empty($fila['numero_orden']))
            ->unique('numero_orden')
            ->values()
            ->all();

        $insertadas = 0;

        foreach (array_chunk($datos, self::TAMANO_BLOQUE) as $chunk) {
            $insertData = array_map(fn (array $fila) => $this->mapearAsignacion($fila), $chunk);

            if (!empty($insertData)) {
                DB::table('tbl_asignaciones')->insert($insertData);
                $insertadas += count($insertData);
            }
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
        $datos = $this->leerFilas($archivo);
        $columnasActualizables = array_diff(
            array_keys($this->mapearCerrada([])),
            ['NUMERO_ORDEN']
        );

        $procesadas = 0;

        foreach (array_chunk($datos, self::TAMANO_BLOQUE) as $chunk) {
            // Filtramos únicos por si vienen duplicados en el mismo Excel
            $upsertData = collect($chunk)
                ->filter(fn (array $fila) => !empty($fila['numero_orden']))
                ->map(fn (array $fila) => $this->mapearCerrada($fila))
                ->unique('NUMERO_ORDEN')
                ->values()
                ->toArray();

            if (!empty($upsertData)) {
                DB::table('tbl_cerradas')->upsert(
                    $upsertData,
                    ['NUMERO_ORDEN'], // Llave principal
                    array_values($columnasActualizables)
                );
                $procesadas += count($upsertData);
            }
        }

        return $procesadas;
    }

    /**
     * Lee la hoja activa y devuelve cada fila como array asociativo,
     * usando los encabezados de la fila 1 (en minúsculas) como llaves.
     *
     * @return array<int, array<string, mixed>>
     */
    private function leerFilas(UploadedFile $archivo): array
    {
        $spreadsheet = IOFactory::load($archivo->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $headers = array_map(
            fn ($h) => strtolower(trim((string) $h)),
            array_shift($rows) ?? []
        );

        $datos = [];
        foreach ($rows as $row) {
            if (empty(array_filter($row))) continue;

            $fila = [];
            foreach ($headers as $i => $header) {
                if ($header) {
                    $fila[$header] = $row[$i] ?? null;
                }
            }
            $datos[] = $fila;
        }

        return $datos;
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
