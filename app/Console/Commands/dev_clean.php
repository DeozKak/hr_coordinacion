<?php

namespace App\Console\Commands;
use App\Models\Bitacoras\tbl_dv_insp;
use DateTime;
use Illuminate\Console\Command;

class dev_clean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dev_clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia de estado a las devoluciones que fueron gestionadas para limpiar cuadro
    de devoluciones';

    
    public function handle()
    {
        $gestionadas = tbl_dv_insp::where('ACTIVADO', 1)
        ->where('GESTIONADO', 1)
        ->get();

        foreach ($gestionadas as $gestionada) {
            $gestionada->ACTIVADO = 0;
            $gestionada->save();
        }
        $devoluciones = Tbl_dv_insp::where('ACTIVADO', 1)->get();
        foreach ($devoluciones as $devolucion) {
            if ($devolucion->GESTIONADO == 1) {
                $devolucion->DIAS_SIN_GESTION = 0;
                $devolucion->save();
                continue;
            }
            $fecha_devolucion = new DateTime($devolucion->FECHA_DV);
            $fecha_actual = new DateTime(date('Y-m-d'));
            $diferencia = $fecha_devolucion->diff($fecha_actual);
            $devolucion->DIAS_SIN_GESTION = $diferencia->days;
            $devolucion->save();
        }

        return "Registros Actualizados";
    }
}
