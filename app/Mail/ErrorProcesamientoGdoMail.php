<?php

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ErrorProcesamientoGdoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $userName;
    public $errorMessage;
    public $rowNumber; // <--- NUEVA VARIABLE

    public function __construct($userName, $errorMessage, $rowNumber)
    {
        $this->userName = $userName;
        $this->errorMessage = $errorMessage;
        $this->rowNumber = $rowNumber;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Error Crítico en el Procesamiento GDO',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.error_gdo',
        );
    }
}
