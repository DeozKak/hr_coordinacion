<?php

namespace App\Console\Commands;

use App\Models\AsignadasQuejas;
use Illuminate\Console\Command;
use App\Services\PQRS\CoordinacionUpdateRecepcion;
class Recepcion_quejas extends Command
{

    /**
     * The name and signature of the console command.
     *
     * php artisan app:actualizar_-stickers
     * (Se deja tal cual tu firma para no romper llamadas existentes)
     */
    protected $signature = 'app:update_recepcion_quejas';

    /**
     * The console command description.
     */
    protected $description = 'Cron job: Ejecuta consultas para actualizar estados en las quejas asignadas.';


    public function handle()
    {
        $query = AsignadasQuejas::select("*")->where('estado', 1);
        $completeData = $query->get();

        CoordinacionUpdateRecepcion::Responsables($completeData);
        CoordinacionUpdateRecepcion::verificarYActualizarRecepcion($completeData);

        return Command::SUCCESS;
    }

}
