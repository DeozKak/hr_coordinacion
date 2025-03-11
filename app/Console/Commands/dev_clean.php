<?php

namespace App\Console\Commands;
use App\Models\tbl_dv_insp;
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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gestionadas = tbl_dv_insp::where('ACTIVADO', 1)
        ->where('GESTIONADO', 1)
        ->get();

        foreach ($gestionadas as $gestionada) {
            $gestionada->ACTIVADO = 0;
            $gestionada->save();
        }

        return "Registros Actualizados";
    }
}
