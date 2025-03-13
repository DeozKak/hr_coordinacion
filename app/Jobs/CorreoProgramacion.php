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
    protected $notification = 'Programacion';
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
        $usuarios = User::whereHas('notificationsMail', function ($query) {
            $query->where('Nombre', $this->notification);
        })->get();
        
        foreach ($usuarios as $usuario) {
            $usuario->notify(new Programada($this->user->name, $this->programacion));
        }

    }
}
