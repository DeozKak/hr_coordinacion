<?php

namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\Bitacora;
use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;

class CorreoBitacora implements ShouldQueue
{
    use Dispatchable, Queueable;
    protected $id_bitacora;
    protected $user;
    /**
     * Create a new job instance.
     */
    public function __construct($id_bitacora,$user)
    {
        $this->id_bitacora = $id_bitacora;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Obtener los usuarios que deben recibir la notificación
        $usuarios = User::role(['admin'])->get();
        // Enviar la notificación a cada usuario
        foreach ($usuarios as $usuario) {
            $usuario->notify(new Bitacora($this->user->name, $this->id_bitacora));
        }
    } 
}
