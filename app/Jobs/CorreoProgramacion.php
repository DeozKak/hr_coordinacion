<?php

namespace App\Jobs;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\Programada;
use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Foundation\Queue\Queueable;

class CorreoProgramacion implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $user;
    protected $programacion;
    /**
     * Create a new job instance.
     */
    public function __construct($user , $programacion)
    {
        $this->user = $user;
        $this->programacion = $programacion;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $usuarios = User::role(['admin', 'PQRS', 'Coordinador_RP', 'Coordinador_RN'])->where('state', 1)->get();
        foreach ($usuarios as $usuario) {
            $usuario->notify(new Programada($this->user->name, $this->programacion));
        }

    }
}
