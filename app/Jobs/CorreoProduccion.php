<?php

namespace App\Jobs;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\Produccion;
use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Foundation\Queue\Queueable;

class CorreoProduccion implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $user;
    protected $contrato;
    protected $fecha;
    protected $inspector;
    /**
     * Create a new job instance.
     */
    public function __construct($contrato, $user, $fecha, $inspector)
    {
        $this->contrato = $contrato;
        $this->user = $user;
        $this->fecha = $fecha;
        $this->inspector = $inspector;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $usuarios = User::role(['admin', 'Director', 'Residente'])->get();

        foreach ($usuarios as $usuario) {
            $usuario->notify(new Produccion($this->contrato, $this->user, $this->fecha, $this->inspector));
        }


    }
}
