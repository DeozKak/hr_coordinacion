<?php

namespace App\Notifications;

use DateTime;
use DateTimeZone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\tbl_bitacora_archivo;
class Bitacora extends Notification
{
    use Queueable;

    private $user;
    private $bitacora;
    
    

    /**
     * Create a new notification instance.
     */
    public function __construct($user,$bitacora)
    {
        $this->user = $user;
       
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
        ->subject($archivo->nombre_archivo . '| Generada')
        ->view('mail.bitacora', [
            'user' => $this->user,
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
            'icon' => 'fas fa-file-alt', 
            'text' => 'Bitacora Generada.',
            'user' => $this->user, 
            'link' => route('bitacoras.ver_reporte',['id_bitacora'=>$this->bitacora])
        ];
    }
}
