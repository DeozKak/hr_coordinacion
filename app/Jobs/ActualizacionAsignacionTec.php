<?php

namespace App\Jobs;
use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\tbl_insp_cali;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\containsIdentical;

//use Illuminate\Foundation\Queue\Queueable;

class ActualizacionAsignacionTec implements ShouldQueue
{
    use Queueable, Dispatchable;


    protected $rowData;

    /**
     * Create a new job instance.
     */
    public function __construct($rowData)
    {
        $this->rowData = $rowData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fecha = date('Y-m-d');
        $programacion = tbl_programacion_contrato::where('FECHA_AGENDAMIENTO', '>=', $fecha)
        ->where('ORDEN_TRABAJO', $this->rowData["NUMERO_ORDEN"])
        ->where('CONTRATO', $this->rowData["CONTRATO"])
        ->get();
        if ($programacion->count() > 0) {
            foreach ($programacion as $pro) {
                try {
                    $inspector = tbl_insp_cali::where('id', $this->rowData["ID_TECNICO"])->first();
                    if ($inspector == null) {
                        log::info('No existe el inspector con codigo: ' . $this->rowData["ID_TECNICO"]);
                        continue;
                    }
                    if ($pro->FECHA_AGENDAMIENTO >= date('Y-m-d')) {
                        $pro->TECNICO = $this->rowData["ID_TECNICO"] . '. ' . $inspector->apellidos . ' ' . $inspector->nombres;
                        $pro->save();
                    }
                } catch (\Throwable $th) {
                    Log::error($th);
                }
            }
        }
    }
}
