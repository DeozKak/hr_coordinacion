<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
class mensajeVisita extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:programacion_visita';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /*// Tus credenciales
        $token = 'EAAQ4E5HlwmIBQiYsj6V2p0bNF0eRadr5z0T6YZC9gdMSr9ogJXCNjSMvQZBUeFBjoo36coQrGgOmMfK2T1FAjHYZCvK3Fr9FtwxKT3aeYSM0ZAHxHyGM2HLNZAWkIjXAy2XZCpvkJAjMtOhlO9wuZAEoVbf3w0DcgW2bHpAZAWn7I3NUuY2MuUK6RnIhGqyKfWHUopQ9SOBRjV1JMh41KHRpE7DzmOWue0ZC7RT3zwte0d9gwiK8MOUIW6TOYZBZBJOq4KFLN5y0LcDK7pqaFQraNjO';
        $phoneId = '897325653475062';
        $version = 'v22.0';
        $destinatario = '573184280662';

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $destinatario,
                'type' => 'text', // <--- Aquí está el cambio clave
                'text' => [
                    'preview_url' => false, // true si quieres previsualizar enlaces
                    'body' => 'Hola Francisco 👋, este es un mensaje libre. Puedo escribir lo que quiera aquí sin pedir permiso a Meta.'
                ]
            ]);

        dd($response->json());*/
        // Tus datos (idealmente sacados del .env)
        $token = 'EAAQ4E5HlwmIBQgjz1GzBMIRV5Gx0uVvZBrtLQBFlG5CzYiUYJm5qeQIqwCy2GCcufgxBdjQyDZAS6KZAEE4ZCozMM0u8Y9PPS9n4ZApFGsjmz2ciA59bNZCM80oduifNZA30LdxYPGs1BxEJZAVST5mbOjxxEYdggSRbzybYCujl3tpZAN5vlT0LJvMloUPRrUGQV0v0geUZCHn3787uZCfWQCa0OzI6BQr71tDUQLGvy5cI6B4cp7aslW0x7FlQTSgvLR25eJqNPZCCXCMqwZA8d4pNYBAZDZD';
        $phoneId = '897325653475062';
        $version = 'v22.0';
        $destinatario = '573184280662'; // Tu número

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $destinatario,
                'type' => 'template',
                'template' => [
                    'name' => 'programacion_visita', // Asegúrate que el nombre sea EXACTO al de Meta
                    'language' => [
                        'code' => 'es' // Spanish (COL) es es_CO
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'parameter_name' => 'nom_usuario',
                                    'text' => 'Francisco Zuluaga'
                                ],
                                [
                                    'type' => 'text',
                                    'parameter_name' => 'fecha_agendamiento',
                                    'text' => '01/12/2025 de 8:00 a 10:00 AM'
                                ],
                                [
                                    'type' => 'text',
                                    'parameter_name' => 'inspector',
                                    'text' => 'ALVARADO VARGAS JONIER ALFONSO'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
        dd($response->json());
        //return $response->json();
    }
}
