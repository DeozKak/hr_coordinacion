<?php

namespace App\Services\PQRS;

use App\Jobs\ProcessExcelFileMacros;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use App\Models\AsignadasQuejas;

class CoordinacionPQRSImportService
{


    public function processDualFiles($pathAsignadas, $nameAsignadas, $pathCerradas, $nameCerradas): array
    {
        // Define las cabeceras esperadas para cada archivo (ajusta según necesites)
        $headersAsignadas = ["NUMERO_ORDEN", "CONTRATO", "NUMERO_SOLICITUD", "TIPO_SOLICITUD", "DESCRIPCION_SOLICITUD", "CEDULA"];
        $headersCerradas = ["NUMERO_ORDEN", "CONTRATO", "FECHA_LEGALIZACIÓN", "DESC_CAUSAL_LEGALIZACIÓN", "OBSERVACIÓN_LEGALIZACIÓN"]; // Ejemplo de cabeceras
        try {

            // 1. Extraer la primera fila (los encabezados) de cada archivo como un array
            $rowHeaderAsignadas = $this->getFirstRowAsArray($pathAsignadas);
            $rowHeaderCerradas = $this->getFirstRowAsArray($pathCerradas);
            // 1. Validar cabeceras del archivo "Asignadas"
            $isValidAsignadas = $this->validacionConArray($rowHeaderAsignadas, $headersAsignadas);
            if (!$isValidAsignadas) {
                Storage::delete([$pathAsignadas, $pathCerradas]);
                return ['errors' => 'El archivo de Asignadas no cumple con las cabeceras requeridas.'];
            }

            // 2. Validar cabeceras del archivo "Cerradas"
            $isValidCerradas = $this->validacionConArray($rowHeaderCerradas, $headersCerradas);
            if (!$isValidCerradas) {
                Storage::delete([$pathAsignadas, $pathCerradas]);
                return ['errors' => 'El archivo de Cerradas no cumple con las cabeceras requeridas.'];
            }

            // 3. Procesar e insertar los datos en la base de datos
            DB::beginTransaction();

            // Lógica para insertar Asignadas (ajusta el nombre de la tabla)
            $this->insertDataFromFile($pathAsignadas, function ($cells) {
                return [
                    'NUMERO_ORDEN' => $cells[0] ?? null,
                    'CONTRATO' => $cells[1] ?? null,
                    'NUMERO_SOLICITUD' => $cells[2] ?? null,
                    'TIPO_SOLICITUD' => $cells[3] ?? null,
                    'DESCRIPCION_SOLICITUD' => $cells[4] ?? null,
                    'CEDULA' => $cells[5] ?? null,
                    'NOMBRE' => $cells[6] ?? null,
                    'DESC_DEPART' => $cells[7] ?? null,
                    'DESC_LOCALIDAD' => $cells[8] ?? null,
                    'BARRIO' => $cells[9] ?? null,
                    'DIRECCION' => $cells[10] ?? null,
                    'GPS' => $cells[11] ?? null,
                    'DESC_CATEGORIA' => $cells[12] ?? null,
                    'COD_UNIDAD_OPER' => $cells[13] ?? null,
                    'DESC_TIPO_TRABAJO' => $cells[14] ?? null,
                    'FECHA_ASIGNACION' => $this->cleanDate($cells[15] ?? null),
                    'OBSERVACION_SOLICITUD' => $cells[16] ?? null,
                    'FECHA_CIERRE_ULTIMA' => $this->cleanDate($cells[17] ?? null),
                    'OBSERVACIÓN_CIERRE_ULTIMA' => $cells[18] ?? null,
                    'TIPO_TRABAJO_CIERRE_ULTIMA' => $cells[19] ?? null,
                    'DESC_CAUSAL_CIERRE_ULTIMA' => $cells[20] ?? null,
                    'FECHA_ASIGNACIÓN_ULTIMA' => $this->cleanDate($cells[21] ?? null),
                    'OBSERVACIÓN_ASIGNACIÓN_ULTIMA' => $cells[22] ?? null,
                    'GESTIÓN_ASIGNACIÓN_ULTIMA' => $cells[23] ?? null,
                    'TIPO_TRABAJO_ASIGNACIÓN_ULTIMA' => $cells[24] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            // Lógica para procesar y actualizar Cerradas
            $this->processCerradasFromFile($pathCerradas);

            DB::commit();

            // 4. Limpiar los archivos temporales
            Storage::delete([$pathAsignadas, $pathCerradas]);

            return ['message' => 'Los archivos han sido procesados y cargados exitosamente.'];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error("Error procesando archivos Excel síncronamente: " . $e->getMessage());
            Storage::delete([$pathAsignadas, $pathCerradas]);
            return ['errors' => 'No se pudo procesar y guardar la información de los archivos.'];
        }

    }

    /**
     * Función auxiliar para obtener únicamente la primera fila del archivo en formato array
     */
    private function getFirstRowAsArray(string $filePath): array
    {

        // Usamos IOFactory que soporta automáticamente tanto .xls como .xlsx
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/' . $filePath));
        $worksheet = $spreadsheet->getActiveSheet();

        $firstRowArray = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $firstRowArray[] = $cell->getValue();
            }
            break; // Detenemos el iterador apenas obtenemos la primera fila
        }

        return $firstRowArray;
    }


    /**
     * Lee un archivo e inserta su contenido por lotes, ignorando la primera fila (cabeceras)
     */
    private function insertDataFromFile($filePath, callable $mapRowFunc): void
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/' . $filePath));
        $worksheet = $spreadsheet->getActiveSheet();

        // Al ser archivos pequeños, podemos pasarlo a un array de una vez
        $rows = $worksheet->toArray();

        $batch = [];
        $batchSize = 500;

        foreach ($rows as $index => $cells) {
            if ($index === 0) {
                continue; // Saltar las cabeceras (índice 0 en el array)
            }

            // Evitar procesar filas que vengan completamente vacías
            if (empty(array_filter($cells))) {
                continue;
            }

            $batch[] = $mapRowFunc($cells);

            if (count($batch) >= $batchSize) {
                AsignadasQuejas::insertOrIgnore($batch);
                $batch = [];
            }
        }

        // Insertar los registros sobrantes
        if (count($batch) > 0) {
            AsignadasQuejas::insertOrIgnore($batch);
        }

    }

    /**
     * Lee el archivo de Cerradas, busca coincidencias en asignadas_quejas
     * y si existe, actualiza el estado y los 3 campos adicionales.
     */
    private function processCerradasFromFile(string $filePath): void
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/' . $filePath));
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        foreach ($rows as $index => $cells) {
            if ($index === 0) {
                continue; // Saltar las cabeceras
            }

            // Evitar procesar filas que vengan completamente vacías
            if (empty(array_filter($cells))) {
                continue;
            }

            $numeroOrden = $cells[0] ?? null;
            $contrato = $cells[1] ?? null;

            if ($numeroOrden && $contrato) {

                // Buscamos si existe la orden en la tabla
                $queja = AsignadasQuejas::where('NUMERO_ORDEN', $numeroOrden)
                    ->where('CONTRATO', $contrato)
                    ->first();

                if ($queja) {
                    // 1. Obtenemos la fecha de legalización limpia
                    $fechaLegalizacionStr = $this->cleanDate($cells[2] ?? null);

                    $diasFaltantes = null;
                    $fechaLimiteStr = $queja->FECHA_LIMITE; // Mantenemos la actual por si acaso

                    // 2. Lógica para calcular Días Faltantes basado en la legalización
                    if (!empty($queja->FECHA_ASIGNACION) && !empty($fechaLegalizacionStr)) {
                        try {
                            // --- Parsear FECHA_ASIGNACION ---
                            $fechaCortaAsig = explode(' ', trim($queja->FECHA_ASIGNACION))[0];
                            if (strpos($fechaCortaAsig, '/') !== false) {
                                $carbonAsignacion = Carbon::createFromFormat('d/m/Y', $fechaCortaAsig)->startOfDay();
                            } elseif (preg_match('/^\d{4}-/', $fechaCortaAsig)) {
                                $carbonAsignacion = Carbon::createFromFormat('Y-m-d', $fechaCortaAsig)->startOfDay();
                            } else {
                                $carbonAsignacion = Carbon::createFromFormat('d-m-Y', $fechaCortaAsig)->startOfDay();
                            }

                            // --- Parsear FECHA_LEGALIZACION ---
                            $fechaCortaLeg = explode(' ', trim($fechaLegalizacionStr))[0];
                            if (strpos($fechaCortaLeg, '/') !== false) {
                                $carbonLegalizacion = Carbon::createFromFormat('d/m/Y', $fechaCortaLeg)->startOfDay();
                            } elseif (preg_match('/^\d{4}-/', $fechaCortaLeg)) {
                                $carbonLegalizacion = Carbon::createFromFormat('Y-m-d', $fechaCortaLeg)->startOfDay();
                            } else {
                                $carbonLegalizacion = Carbon::createFromFormat('d-m-Y', $fechaCortaLeg)->startOfDay();
                            }

                            // --- Calcular Fecha Límite (+ 4 días desde asignación) ---
                            $carbonLimite = $carbonAsignacion->copy()->addDays(4);
                            $fechaLimiteStr = $carbonLimite->format('Y-m-d');

                            // --- Calcular Días Faltantes al momento del cierre ---
                            // Si se legalizó antes del límite = positivo (A tiempo)
                            // Si se legalizó después del límite = negativo (Vencida)
                            $diasFaltantes = $carbonLegalizacion->diffInDays($carbonLimite, false);

                        } catch (\Exception $e) {
                            // Si la fecha viene corrupta o vacía, ignoramos el cálculo matematico
                            // para que el sistema no se detenga con un error fatal.
                        }
                    }
                    //dd($diasFaltantes);
                    // 3. Actualizamos los datos en la base de datos
                    $queja->update([
                        'estado' => 0,
                        'FECHA_LEGALIZACION' => $fechaLegalizacionStr,
                        'DESC_CAUSAL_LEGALIZACION' => $cells[3] ?? null,
                        'OBSERVACION_LEGALIZACION' => $cells[4] ?? null,
                        'FECHA_LIMITE' => $fechaLimiteStr,
                        // Si el cálculo fue exitoso guarda el nuevo, si falló, deja el que ya tenía.
                        'DIAS_FALTANTES' => $diasFaltantes !== null ? $diasFaltantes : $queja->DIAS_FALTANTES,
                    ]);
                }
            }
        }
    }


    private function validacionConArray(array $headerRow, array $expectedHeaders): bool
    {
        // Comparamos solo las primeras columnas necesarias
        $headersToValidate = array_slice($headerRow, 0, count($expectedHeaders));

        // Eliminamos espacios adicionales de los headers para evitar errores
        $headersToValidate = array_map('trim', $headersToValidate);
        $expectedHeaders = array_map('trim', $expectedHeaders);

        return $headersToValidate === $expectedHeaders;
    }

    /**
     * Limpia la fecha para guardar unicamente "DD/MM/YYYY" o "YYYY-MM-DD"
     * removiendo cualquier valor de horas, minutos o segundos.
     */
    private function cleanDate($dateValue)
    {
        if (empty($dateValue)) {
            return null;
        }

        // Dividimos el string por el espacio vacío y tomamos el primer elemento (la fecha)
        $parts = explode(' ', trim($dateValue));
        return $parts[0] ?? null;
    }


}
