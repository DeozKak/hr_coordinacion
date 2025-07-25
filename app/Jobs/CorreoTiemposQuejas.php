<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\TiemposQuejas;
use App\Models\tbl_queja;
class CorreoTiemposQuejas implements ShouldQueue
{
    use Dispatchable, \Illuminate\Bus\Queueable;

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
        $quejas = tbl_queja::where('DIAS','>=','3')->get();

        if ($quejas->count() === 0) {
            // No hay quejas, no se envía correo
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
