<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResultadosGdoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $rutaArchivo;

    public function __construct($userName, $rutaArchivo)
    {
        $this->userName = $userName;
        $this->rutaArchivo = $rutaArchivo;
    }

    public function build()
    {
        return $this->subject('✅ Resultados de Programación GDO Procesados')
            ->view('mail.resultados_gdo') // Apunta a la vista que crearemos en el paso 2
            ->attach($this->rutaArchivo, [
                'as' => 'Resultados_GDO.xlsx',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
