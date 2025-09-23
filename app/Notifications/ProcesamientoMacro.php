<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProcesamientoMacro extends Notification
{
    use Queueable;

    private $user;
    private $archivo;
    private $stats;

    private $error;

    /**
     * Create a new notification instance.
     */
    public function __construct($user, $archivo, array $stats = [], array $error = null)
    {
        $this->user = $user;
        $this->archivo = $archivo;
        $this->stats = $stats;
        $this->error = $error;
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
        if ($this->error) {
            return (new MailMessage)
                ->subject('⚠️ Alerta: Falló el procesamiento de la macro "' . $this->archivo . '"')
                ->view('mail.FallaProcesamientoMacro', [ // Vista para el correo de error
                    'nombreUsuario' => $this->user->name,
                    'nombreArchivo' => $this->archivo,
                    'error' => $this->error
                ]);
        }

        return (new MailMessage)
            ->subject('¡Éxito! Se ha procesado la macro "' . $this->archivo.'"')
            ->view('mail.ProcesamientoMacro', [ // Vista para el correo de éxito
                'nombreUsuario' => $this->user->name,
                'nombreArchivo' => $this->archivo,
                'stats' => $this->stats,
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
