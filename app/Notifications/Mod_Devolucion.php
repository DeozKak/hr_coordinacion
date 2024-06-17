<?php

namespace App\Notifications;

use DateTime;
use DateTimeZone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\tbl_bitacora_archivo;

class Mod_Devolucion extends Notification
{
    use Queueable;

    private $user;
    private $contrato;
    private $bitacora;
    
    /**
     * Create a new notification instance.
     */
    public function __construct($user,$contrato,$bitacora)
    {
        $this->user = $user;
        $this->contrato = $contrato;
        $this->bitacora = $bitacora;
    
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
        $archivo = tbl_bitacora_archivo::where('id',$this->bitacora)->first();
        
        return (new MailMessage)
        ->subject("Contrato " . $this->contrato . '  Gestionado')
        ->view('mail.devolucion', [
            'user' => $this->user,
            'contrato' => $this->contrato,
            'archivo' => $archivo,
            'bitacora' => $this->bitacora,
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
            'icon' => 'fas fa-fw fa-check-circle', 
            'text' => 'Contrato '.$this->contrato.' gestionado.',
            'user' => $this->user, 
            'link' => route('bitacoras.ver_reporte',['id_bitacora'=>$this->bitacora])
        ];
    }
}
