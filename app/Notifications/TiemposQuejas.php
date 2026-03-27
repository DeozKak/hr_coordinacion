<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\tbl_queja;
use App\Models\asignadas_quejas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TiemposQuejas extends Notification
{
    use Queueable;


    /**
     * Create a new notification instance.
     */
    public function __construct()
    {

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
        // Hacemos join para traer los datos del inspector
        $quejas = asignadas_quejas::query()
            ->select([
                'CONTRATO',
                'DESC_LOCALIDAD',
                'BARRIO',
                'DIRECCION',
                'DIAS_FALTANTES',
                'ASIGNADO',
                'SUPERVISOR',
                'RECEPCION'
            ])

            // CAMBIO: Agrupamos la lógica antigua y agregamos la excepción de GDW
            ->where(function ($query) {
                // Regla 1: Pendientes por recibir con pocos días
                $query->where(function ($q) {
                    $q->whereNull('RECEPCION')
                        ->where('DIAS_FALTANTES', '<=', 3);
                })
                    // Regla 2: O que ya sean GDW
                    ->orWhere('RECEPCION', 'GDW');
            })
            ->whereNotNull('ASIGNADO')
            // CAMBIO AQUÍ: ASC para que los negativos (-5, -4...) salgan primero
            ->orderBy('DIAS_FALTANTES', 'ASC')
            ->get();


        return (new MailMessage)
            ->subject('Reporte Automático:Quejas por vencer (< 3 días) y GDW ' . date('d-m-Y'))
            ->view('mail.tiemposQuejas', [
                'quejas' => $quejas,
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
