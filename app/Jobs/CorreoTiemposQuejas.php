<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\TiemposQuejas;
use App\Models\tbl_queja;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CorreoTiemposQuejas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notification = "Tiempos Quejas";

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $quejas = tbl_queja::where(function ($query) {
            // Condición original: Más de 3 días Y recepción vacía
            $query->where('DIAS', '>=', 3)
                ->whereNull('recepcion');
        })
            ->orWhere('recepcion', 'GDW') // O que recepción sea GDW (sin importar los días)
            ->get();

        if ($quejas->count() === 0) {

            return;
        }

        $users = User::whereHas('notificationsMail', function ($query) {
            $query->where('Nombre', $this->notification);
        })->get();

        foreach ($users as $user) {
            $user->notify(new TiemposQuejas());
        }
    }
}
