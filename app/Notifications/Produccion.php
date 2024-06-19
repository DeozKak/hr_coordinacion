<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Produccion extends Notification
{
    use Queueable;


    private $contrato;
    private $user;
    private $fecha;
    private $inspector;
    
    /**
     * Create a new notification instance.
     */
    public function __construct($contrato, $user, $fecha, $inspector)
    {
        $this->contrato = $contrato;
        $this->user = $user;
        $this->fecha = $fecha;
        $this->inspector = $inspector;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
        ->subject("Contrato ")
        ->view('mail.produccion',[
            'contrato' => $this->contrato,
            'user' => $this->user,
            'fecha' => $this->fecha,
            'inspector' => $this->inspector
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
            //
        ];
    }
}
