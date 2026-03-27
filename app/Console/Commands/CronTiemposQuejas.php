<?php

namespace App\Console\Commands;

use App\Models\asignadas_quejas;
use App\Models\User;
use App\Notifications\TiemposQuejas;
use Illuminate\Console\Command;


class CronTiemposQuejas extends Command
{

    /**
     * The name and signature of the console command.
     *
     * php artisan app:actualizar_-stickers
     * (Se deja tal cual tu firma para no romper llamadas existentes)
     */
    protected $signature = 'app:tiempos_quejas';

    /**
     * The console command description.
     */
    protected $description = 'Cron job: ejecuta reporte automatico de quejas.';

    protected $notification = "Tiempos Quejas";
    public function handle(): int
    {

        $quejas = asignadas_quejas::where(function ($query) {
            // Condición original: Más de 3 días Y recepción vacía
            $query->where('DIAS_FALTANTES', '<=', 3)
                ->whereNull('RECEPCION');
        })
            ->whereNotNull('ASIGNADO')
            ->orWhere('RECEPCION', 'GDW') // O que recepción sea GDW (sin importar los días)
            ->get();

        if ($quejas->count() === 0) {
            return 0;
        }

        $users = User::whereHas('notificationsMail', function ($query) {
            $query->where('Nombre', $this->notification);
        })->get();

        foreach ($users as $user) {
            $user->notify(new TiemposQuejas());
        }

        return Command::SUCCESS;

    }

}
