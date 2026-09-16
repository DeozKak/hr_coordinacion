<?php

namespace App\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->comprobarConexionSmtpAntesDeCadaEnvio();
    }

    /**
     * Que el transporte SMTP compruebe la conexión antes de cada correo.
     *
     * El worker de la cola vive hasta 30 minutos y reutiliza la misma conexión
     * SMTP para todos los correos que envía. Symfony sólo comprueba que siga
     * viva —con un NOOP, y reconectando si falla— cuando han pasado más de
     * 100 segundos desde el último envío. Hostinger corta antes una conexión
     * inactiva, así que un correo que llegaba entre ese corte y los 100 s se
     * escribía sobre un socket muerto y fallaba con:
     *
     *     421 4.4.2 smtp.hostinger.com Error: timeout exceeded
     *
     * Con el umbral a cero se comprueba siempre. Cuesta un NOOP por correo, y
     * en el primero no hace nada porque todavía no hay conexión que comprobar.
     *
     * Va en el evento de envío y no al crear el transporte porque Laravel no
     * ofrece un punto donde configurarlo, y así se aplica al mailer que
     * realmente está enviando, sea cual sea.
     */
    private function comprobarConexionSmtpAntesDeCadaEnvio(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $evento) {
            $transporte = Mail::mailer($evento->data['mailer'] ?? null)->getSymfonyTransport();

            if ($transporte instanceof SmtpTransport) {
                $transporte->setPingThreshold(0);
            }
        });
    }
}
