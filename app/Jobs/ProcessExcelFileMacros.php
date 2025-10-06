<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Programacion\tbl_programacion_base;
use App\Notifications\ProcesamientoMacro;

class ProcessExcelFileMacros implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $user;
    protected $originalName;
    public $timeout = 600; // Segundos
    /**
     * Create a new job instance.
     *
     * @param string $filePath La ruta relativa al archivo en storage/app
     */
    public function __construct(string $filePath, $user, $originalName)
    {
        $this->filePath = $filePath;
        $this->user = $user;
        $this->originalName = $originalName;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $startTime = microtime(true); // Registra el tiempo de inicio
        $fullPath = storage_path('app/' . $this->filePath);
        $indicador = '';
        $filaError = 0; // Para rastrear la fila en caso de error

        // Inicializa el array de estadísticas
        $stats = [
            'totalProcesados' => 0,
            'creados' => 0,
            'actualizados' => 0, // Lo mantenemos por si en el futuro usas upsert
            'duracion' => 0
        ];

        try {
            $reader = ReaderEntityFactory::createXLSXReader();
            $reader->open($fullPath);

            $registros = [];
            $tamañoLote = 2000;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    $filaError = $index;
                    if ($index === 1) {
                        continue; // Saltamos los encabezados, ya fueron validados
                    }

                    $rowDataArray = $row->toArray();

                    if (empty(array_filter($rowDataArray))) {
                        continue;
                    }

                    // Lógica para borrar registros basada en la segunda fila
                    if ($index === 2) {
                        switch ($rowDataArray[16]) {
                            case '12161':
                            case '10444':
                                tbl_programacion_base::whereIn('ID_TIPO_TRABAJO', ['10444', '12161'])->delete();
                                $indicador = 'RP';
                                break;
                            case '12163':
                            case '12164':
                                tbl_programacion_base::whereIn('ID_TIPO_TRABAJO', ['12163', '12164'])->delete();
                                $indicador = 'SA';
                                break;
                            default:
                                tbl_programacion_base::whereIn('ID_TIPO_TRABAJO', ['12162'])->delete();
                                $indicador = 'RN';
                                break;
                        }
                    }

                    // --- Incrementa los contadores para cada fila válida ---
                    $stats['totalProcesados']++;
                    // Como primero borras y luego insertas, cada registro cuenta como "creado"
                    $stats['creados']++;

                    if ($indicador == 'RP') {
                        $orden = $rowDataArray[19] ?? null;
                        $tipo_trabajo = "12161";
                        if ($orden == '') {
                            $orden = $rowDataArray[0] ?? null;
                            $tipo_trabajo = "10444";
                        }
                    }
                    // en caso de macro de nuevas, se toman de referencia otras columnas
                    if ($indicador == 'RN') {
                        $tipo_trabajo = "12162";
                        $fechaAsignacionExcel = $rowDataArray[31] ?? null;
                        $tecnico = $rowDataArray[30] ?? null;
                        $fechaRecepcionExcel = $rowDataArray[33] ?? null;
                        $estado_recepcion = ($rowDataArray[32] ?? 0) === '' ? 0 : ($rowDataArray[32] ?? 0);
                        $nombre = $rowDataArray[7] ?? null;
                        $barrio = $rowDataArray[10] ?? null;
                        $direccion = $rowDataArray[11] ?? null;
                        $categoria = $rowDataArray[13] ?? null;
                        $localidad = $rowDataArray[9] ?? null;

                    }else{
                        // EN CASO DE QUE NO ES MACRO DE NUEVAS SE ASIGNA EL VALOR DE OTRA COLUMNA
                        $estado_recepcion = ($rowDataArray[36] ?? 0) === '' ? 0 : ($rowDataArray[36] ?? 0);
                    }

                    // Mapeo de datos
                    $fechaAsignacion = $this->formatDate($fechaAsignacionExcel ?? $rowDataArray[35] ?? null);
                    $fechaRecepcion = $this->formatDate($fechaRecepcionExcel ?? $rowDataArray[37] ?? null);
                    // en caso de macro de nuevas, se toman de referencia otras columnas
                    $rowData = [
                        "NUMERO_ORDEN" => $orden ?? $rowDataArray[0] ?? null,
                        "CONTRATO" => $rowDataArray[1] ?? null,
                        "DESC_ESTADO_PROD" => $rowDataArray[74] ?? "Activo",
                        "NOMBRE" => $nombre ?? $rowDataArray[6] ?? null,
                        "DESC_LOCALIDAD" => $localidad ?? $rowDataArray[8] ?? null,
                        "BARRIO" => $barrio ?? $rowDataArray[9] ?? null,
                        "DIRECCION" => $direccion ?? $rowDataArray[10] ?? null,
                        "NOM_CATE" => $categoria ?? $rowDataArray[14] ?? null,
                        "ID_TIPO_TRABAJO" => $tipo_trabajo ?? $rowDataArray[16] ?? null,
                        "ID_TECNICO" => $tecnico ?? $rowDataArray[34] ?? null,
                        "FECHA_ASIGNACION" => $fechaAsignacion,
                        "ESTADO_RECEPCION" => $estado_recepcion,
                        "FECHA_RECEPCION" => $fechaRecepcion,
                    ];

                    $registros[] = array_map(fn($v) => $v === '' ? null : $v, $rowData);

                    if (count($registros) >= $tamañoLote) {
                        $this->insertarLote($registros);
                        $registros = [];
                    }
                }
            }

            if (!empty($registros)) {
                $this->insertarLote($registros);
            }

            $reader->close();
            // --- 3. FINALIZACIÓN Y NOTIFICACIÓN DE ÉXITO ---
            $stats['duracion'] = round(microtime(true) - $startTime); // Calcula la duración total
            Log::info("Archivo {$this->filePath} procesado exitosamente.");
            $notification = new ProcesamientoMacro($this->user, $this->originalName, $stats);
            // Envía la notificación de éxito con las estadísticas
            $this->user->notify($notification->delay(now()->addSeconds(10)));

        } catch (\Exception $e) {
            // --- 4. MANEJO DE ERRORES Y NOTIFICACIÓN DE FALLO ---
            Log::error("Fallo el Job en la fila {$filaError} para el archivo {$this->filePath}: " . $e->getMessage());

            // Prepara los detalles del error para la notificación
            $errorDetails = [
                'mensaje' => $e->getMessage(),
                'fila' => $filaError
            ];
            $errorNotification = new ProcesamientoMacro($this->user, $this->originalName, [], $errorDetails);

            // Envía la notificación de error también con un retraso para ser consistente
            $this->user->notify($errorNotification->delay(now()->addSeconds(10)));

            $this->fail($e);
        } finally {
            Storage::delete($this->filePath);
        }
    }

    private function insertarLote(array $registros): void
    {
        // En un Job, es mejor envolver cada lote en su propia transacción.
        DB::transaction(function () use ($registros) {

            tbl_programacion_base::insert($registros);
            // O usa upsert si es lo que necesitas
        });
    }

    private function formatDate($excelDate): ?string
    {
        if ($excelDate instanceof \DateTime) {
            return $excelDate->format('Y-m-d');
        }
        if (is_numeric($excelDate)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

}
