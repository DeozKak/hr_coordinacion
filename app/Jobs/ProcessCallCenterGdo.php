<?php

namespace App\Jobs;

use App\Mail\ErrorProcesamientoGdoMail;
use App\Mail\ResultadosGdoMail;
use App\Models\Programacion\tbl_programacion_usuario;
use App\Models\Programacion\tbl_programacion_contrato;
use App\Services\ExtraerFechas;
use App\Services\ProgramacionService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProcessCallCenterGdo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $programacionId;
    protected $userId;

    // Aumentamos el tiempo de espera del Job por si son 3000 registros (aprox 3 horas máximo)
    public $timeout = 10800;

    public function __construct($filePath, $programacionId, $userId)
    {
        $this->filePath = $filePath;
        $this->programacionId = $programacionId;
        $this->userId = $userId;
    }

    public function handle(ProgramacionService $programacionService)
    {
        // VARIABLE RASTREADORA: Nos dirá dónde estábamos si algo falla
        $registroActual = "Leyendo encabezados iniciales...";

        try {
            date_default_timezone_set('America/Bogota');
            $date = Datetime::createFromFormat('Y/m/d', Carbon::now()->format('Y/m/d'));

            $fullPath = storage_path('app/' . $this->filePath);
            $file = IOFactory::load($fullPath);
            $worksheet = $file->getActiveSheet();
            $worksheet->setCellValue('AB1', 'Resultado');

            $fechasExtractor = new ExtraerFechas();

            foreach ($worksheet->getRowIterator() as $row) {
                $rowIndex = $row->getRowIndex();

                // ACTUALIZAMOS EL RASTREADOR
                $registroActual = "Fila " . $rowIndex;

                if ($rowIndex === 1) continue;

                $rango = 'A' . $rowIndex . ':AA' . $rowIndex;
                $string = $worksheet->getCell('S' . $rowIndex)->getValue();
                if (empty($string)) continue;

                $ordenTrabajo = $worksheet->getCell('A' . $rowIndex)->getValue();
                $contrato     = $worksheet->getCell('B' . $rowIndex)->getValue();
                $tipoTrabajo  = $worksheet->getCell('Q' . $rowIndex)->getValue();

                $exist = tbl_programacion_contrato::where('CONTRATO', $contrato)
                    ->where('ORDEN_TRABAJO', $ordenTrabajo)->exists();
                $executed = $programacionService->findExecuted($contrato, $tipoTrabajo, $ordenTrabajo);

                if ($exist) {
                    $this->setCellStatus($worksheet, $rowIndex, $rango, 'Ya existe una programación', 'FFECA2CE');
                    continue;
                }
                if ($executed) {
                    $this->setCellStatus($worksheet, $rowIndex, $rango, 'Orden Ya Ejecutada', 'FFECA2CE');
                    continue;
                }

                // Pausa obligatoria para la API
                usleep(5000000);

                $valorNumericoExcel = $worksheet->getCell('R' . $rowIndex)->getValue();
                $fechaComoDateTime = Date::excelToDateTimeObject($valorNumericoExcel);

                $resultadoIA = $fechasExtractor->findDates($string, $fechaComoDateTime->format('Y-m-d'), $rowIndex);
                $arrayFechas = $resultadoIA['fechas'];
                $jornada = $resultadoIA['jornada'];

                if (is_array($arrayFechas) && count($arrayFechas) > 0) {
                    $fechaArray = Carbon::instance($arrayFechas[0]);
                    $fechaComparar = Carbon::instance($date);

                    if (count($arrayFechas) >= 2) {
                        $this->setCellStatus($worksheet, $rowIndex, $rango, 'Múltiples fechas detectadas', 'FFECC862');
                    } else if ($date->format('Y-m-d') > $arrayFechas[0]->format('Y-m-d')) {
                        $this->setCellStatus($worksheet, $rowIndex, $rango, 'Fecha pasada: ' . $arrayFechas[0]->format('Y-m-d'), 'FFECC862');
                    } else {
                        $filaArray = $worksheet->rangeToArray('A'.$rowIndex.':AA'.$rowIndex, null, true, false, true);
                        $valoresFila = $filaArray[$rowIndex];
                        $resultado = $this->insertarDatosGDO($valoresFila, $this->programacionId, $arrayFechas[0]->format('Y-m-d'), $string, $jornada, $programacionService);

                        if ($resultado == 1) {
                            $mensajeJornada = $jornada ? " ($jornada)" : "";
                            $this->setCellStatus($worksheet, $rowIndex, $rango, 'Programado para ' . $arrayFechas[0]->format('Y-m-d') . $mensajeJornada, 'FF6FF658');
                        }
                    }
                } else {
                    $this->setCellStatus($worksheet, $rowIndex, $rango, 'Sin fecha válida detectada', null);
                }
            }

            // Cambiamos el estado al terminar el bucle
            $registroActual = "Guardando el archivo final...";

            $nombreArchivo = 'Resultados_GDO_' . time() . '.xlsx';
            $rutaFinal = storage_path('app/public/resultados_excel/' . $nombreArchivo);

            if (!file_exists(storage_path('app/public/resultados_excel'))) {
                mkdir(storage_path('app/public/resultados_excel'), 0777, true);
            }

            $writer = IOFactory::createWriter($file, 'Xlsx');
            $writer->setPreCalculateFormulas(false);
            $writer->save($rutaFinal);
            $file->disconnectWorksheets();
            unset($file);

            $programacion = tbl_programacion_usuario::find($this->programacionId);
            if ($programacion) { $programacion->finished = 1; $programacion->save(); }

            // Enviar Correo de Éxito
            $user = \App\Models\User::find($this->userId);
            if ($user && !empty($user->email)) {
                Mail::to($user->email)->send(new ResultadosGdoMail($user->name, $rutaFinal));
            }

            Storage::delete($this->filePath);

        } catch (\Exception $e) {
            Log::error("Error fatal en ProcessCallCenterGdo: " . $e->getMessage() . " | Registro: " . $registroActual);

            // PASAMOS EL $registroActual AL CORREO
            $user = \App\Models\User::find($this->userId);
            if ($user && !empty($user->email)) {
                Mail::to($user->email)->send(new ErrorProcesamientoGdoMail($user->name, $e->getMessage(), $registroActual));
            }

            $programacion = tbl_programacion_usuario::find($this->programacionId);
            if ($programacion) { $programacion->finished = 1; $programacion->save(); }

            throw $e;
        }
    }

    private function setCellStatus($worksheet, $rowIndex, $rango, $mensaje, $colorHex)
    {
        $worksheet->setCellValue('AB' . $rowIndex, $mensaje);
        if ($colorHex) {
            $worksheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($colorHex);
        }
    }

    private function insertarDatosGDO($row, $id_programacion, $scheduling, $observation, $jornada, $programacionService)
    {
        // Nota: El exist y el executed ya se validaron al principio del bucle, pero se mantienen aquí por integridad de la capa de datos.
        $exist = tbl_programacion_contrato::where('CONTRATO', $row['B'])->where('ORDEN_TRABAJO', $row['A'])->exists();
        $executed = $programacionService->findExecuted($row['B'], $row['Q'], $row['A']);

        if ($exist) return 0;
        if ($executed) return 3;

        try {
            $registro = new tbl_programacion_contrato();
            $registro->CONTRATO = $row['B'];
            $registro->TIPO_TRABAJO = $row['Q'];
            $registro->FECHA = date('Y-m-d');
            $registro->CELULAR = '-';
            $registro->NOMBRE_USUARIO = $row['G'];
            $registro->ORDEN_TRABAJO = $row['A'];
            $registro->DIRECCION = $row['K'];
            $registro->BARRIO = $row['J'];
            $registro->CIUDAD = $row['I'];
            $registro->ACTIVA = ($row['T'] == 'Activo') ? 'Si' : 'No';
            $registro->SUSPENDIDO = ($row['T'] == 'Activo') ? 'No' : 'Si';
            $registro->CATEGORIA = $row['O'];
            $registro->FECHA_AGENDAMIENTO = $scheduling;
            $registro->OBSERVACIONES = $observation;
            $registro->PORQUE_PROGRAMO = 'PROGRAMACION GDO';
            $registro->TECNICO = '100. OFICINA';
            $registro->JORNADA = $jornada;
            $registro->HORA_INICIO = "06:59:00 a.m.";
            $registro->HORA_FINAL = "04:59:00 p.m.";
            $registro->id_programacion = $id_programacion;
            $registro->mensaje = 1;
            $registro->plantilla = 0;
            $registro->EJECUTADA = 0;
            $registro->save();
            return 1;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return 2;
        }
    }
}
