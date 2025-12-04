<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
class mensajeWhatsapp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mensaje-whatsapp';

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
        // Tus datos (idealmente sacados del .env)
        $token = 'EAAfLxzjus9oBQJJz6zdVsLarLWVKepMndMoLcsnvZAsFinpzkapGrV3IjQlu6aFK1uEqihupe6gUKV6k5kMyP2t2xHqBPR0ANg0iQrG8R19ZCIVW3tjtm2cZAZCavmZAnuLsOtcwY072AbDazCDVaLZBitXCJwgdWBegEGWZCU9RZCsKtLDmUj4I1h4zSV0Ge6JVtky7Cdt4aq3ioVGL7jmsYIF5iV3IBzzzH8RQPV4vz37h5d99hCBrF5kyJ3hnmlY50ZBGAlBXRfZCSo9dqb23DR';
        $phoneId = '936199389570659';
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
                        'code' => 'es_CO' // Spanish (COL) es es_CO
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'parameter_name' => 'nom_cliente',
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
