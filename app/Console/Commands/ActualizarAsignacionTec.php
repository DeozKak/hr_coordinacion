<?php

namespace App\Console\Commands;

use App\Models\Programacion\TblProgramacionBase;
use App\Models\Programacion\TblProgramacionContrato;
use App\Models\TblInspCali;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActualizarAsignacionTec extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:actualizar-asignacion-tec';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $jobId; // ID del registro en la tabla de estado

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fecha = date('Y-m-d');

        // Registrar el inicio del proceso en la tabla 'job_status'
        $this->jobId = DB::table('job_status')->insertGetId([
            'job_name' => 'Actualizar Asignación de Técnicos',
            'status' => 'running',
            'total' => TblProgramacionBase::whereNotNull('ID_TECNICO')->count(), // Total de registros
            'processed' => 0, // Inicialmente procesados
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            // Cargar inspectores en memoria
            $inspectores = TblInspCali::query()
                ->select('id', 'apellidos', 'nombres')
                ->get()
                ->keyBy('id')
                ->toArray();

            // Procesar los contratos base en lotes
            TblProgramacionBase::query()
                ->select('NUMERO_ORDEN', 'CONTRATO', 'ID_TECNICO')
                ->whereNotNull('ID_TECNICO')
                ->chunk(500, function ($contratosBase) use ($fecha, $inspectores) {
                    foreach ($contratosBase as $base) {
                        // Verificar si el técnico existe
                        if (!isset($inspectores[$base->ID_TECNICO])) {
                            $this->warn("Inspector no encontrado para ID_TECNICO: {$base->ID_TECNICO}");
                            // Actualizar el progreso en la tabla 'job_status'
                            DB::table('job_status')
                                ->where('id', $this->jobId)
                                ->increment('processed');

                            continue;
                        }

                        $inspector = $inspectores[$base->ID_TECNICO];
                        $nombreCompleto = "{$base->ID_TECNICO}. {$inspector['apellidos']} {$inspector['nombres']}";

                        // Actualizar contratos relacionados
                        $contratos = TblProgramacionContrato::query()
                            ->where('FECHA_AGENDAMIENTO', '>=', $fecha)
                            ->where('CONTRATO', $base->CONTRATO)
                            ->get();

                        foreach ($contratos as $contrato) {
                            $contrato->TECNICO = $nombreCompleto;
                            $contrato->save();
                        }
                        // Actualizar el progreso en la tabla 'job_status'
                        DB::table('job_status')
                            ->where('id', $this->jobId)
                            ->increment('processed');
                    }
                });

            // Marcar el proceso como completado
            DB::table('job_status')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);

            $this->info('Proceso de actualización completado.');
        } catch (\Exception $e) {
            // Registrar el error si falla
            DB::table('job_status')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'failed',
                    'details' => $e->getMessage(),
                    'updated_at' => now(),
                ]);

            $this->error('Ocurrió un error durante el proceso: ' . $e->getMessage());
        }
    }



}
