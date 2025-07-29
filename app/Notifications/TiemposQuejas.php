<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\tbl_queja;
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
        $quejas = \App\Models\tbl_queja::query()
            ->select([
                'CONTRATO',
                'LOCALIDAD',
                'BARRIO',
                'DIRECCION',
                'DIAS',
                DB::raw("CONCAT(tbl_insp_cali.id, '. ', tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS INSPECTOR"),
                // Aquí el CASE para la excepción:
                DB::raw("CASE
            WHEN tbl_insp_cali.id IN (101, 102, 200) THEN ''
            ELSE users.name
        END AS SUPERVISOR"),
            ])
            ->join('tbl_insp_cali', 'tbl_quejas.INSPECTOR', '=', 'tbl_insp_cali.id')
            ->leftJoin('users', 'tbl_insp_cali.SUPERVISOR', '=', 'users.id')
            ->whereNull('recepcion')
            ->where('DIAS', '>=', 3)
            ->orderBy('DIAS', 'DESC')
            ->get();


        return (new MailMessage)
            ->subject('Reporte de Tiempos de Quejas ' . date('d-m-Y'))
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
