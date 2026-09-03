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
        $token = config('services.meta_whatsapp.token');
        $phoneId = config('services.meta_whatsapp.phone_id');
        $version = config('services.meta_whatsapp.version');
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
        $token = config('services.meta_whatsapp.token');
        $phoneId = config('services.meta_whatsapp.phone_id');
        $version = config('services.meta_whatsapp.version');
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
