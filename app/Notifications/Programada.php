<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\tbl_programacion_usuario;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Programada extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

     private $user;
     private $programacion;
    public function __construct($user,$programacion)
    {
        $this->user = $user;
        $this->programacion = $programacion;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail','database'];
   
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $archivo = tbl_programacion_usuario::where('id',$this->programacion)->first();
        
        return (new MailMessage)
        ->subject('Tabla '.$archivo->nombre. ' | Generada')
        ->view('mail.programada', [
            'user' => $this->user,
            'archivo' => $archivo,
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        
        return [
            'icon' => 'fas fa-file-alt', 
            'text' => 'Tabla Programacion Generada.',
            'user' => $this->user, 
            'link' => route('programacion.show',['id'=>$this->programacion]).'?action=view'
        ];
    }
}
