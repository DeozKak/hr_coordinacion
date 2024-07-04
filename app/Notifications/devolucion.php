<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class devolucion extends Notification
{
    use Queueable;

    private $user;
    private $contrato;
    private $super;

    private $archivo;
    /**
     * Create a new notification instance.
     */
    public function __construct($user, $contrato, $super, $archivo)
    {
        $this->user = $user;
        $this->contrato = $contrato;
        $this->super = $super;
        $this->archivo = $archivo;
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
        ->subject("Contrato " . $this->contrato . '  pasó a devolución')
        ->view('mail.reportDev', [
            'user' => $this->user,
            'contrato' => $this->contrato,
            'super' => $this->super,
            'archivo' => $this->archivo,
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
