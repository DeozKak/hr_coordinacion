<?php

namespace App\Services;

use App\Models\ChatSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Programacion\tbl_programacion_contrato;

class WhatsAppBotService
{
    protected $token;
    protected $phoneId;
    protected $version;

    public function __construct()
    {
        $this->token = config('services.meta_whatsapp.token');
        $this->phoneId = config('services.meta_whatsapp.phone_id');
        $this->version = config('services.meta_whatsapp.version');
    }

    public function procesarMensaje($numero, $mensaje)
    {
        $texto = strtolower(trim($mensaje));

        // 1. Obtener o Crear Sesión
        $session = ChatSession::firstOrCreate(
            ['phone_number' => $numero],
            ['step' => 'START', 'last_activity' => now()]
        );

        // 2. Verificar Timeout (30 min) o Reinicio manual
        $minutosInactivo = now()->diffInMinutes($session->last_activity);

        if ($minutosInactivo > 30 || $texto === 'reiniciar' || $texto === 'menu') {
            $session->update(['step' => 'START']);
            // Si quieres puedes saludar de nuevo aquí
        }

        // Actualizar actividad
        $session->update(['last_activity' => now()]);

        // 3. Ejecutar la Máquina de Estados
        $this->manejarFlujo($session, $texto);
    }

    protected function manejarFlujo($session, $texto)
    {
        $numero = $session->phone_number;

        switch ($session->step) {
            case 'START':
                // EN LUGAR DE TEXTO, ENVIAMOS UNA LISTA
                $this->enviarMenuLista(
                    $numero,
                    "👋 ¡Hola! Somos *E&C Ingeniería*,\ncontratista de Gases de Occidente, nos dedicamos a inspeccionar redes para el suministro de gases combustibles.\n¿En qué podemos ayudarte hoy?
                    \n Selecciona una opción 👇"  ,
                    "Ver Menú", // Texto del botón
                    [
                        'menu_agendar' => '📅 Agendar Visita',
                        'menu_asesor'  => 'talk Hablar con Asesor'
                    ]
                );

                $session->update(['step' => 'MENU_PRINCIPAL']);
                break;

            case 'MENU_PRINCIPAL':
                // AHORA EVALUAMOS LOS IDs QUE DEFINIMOS ARRIBA
                if ($texto === 'menu_agendar') {
                    $this->enviarMensaje($numero, "Perfecto. Por favor escribe el numero de contrato que esta en la factura.");
                    $session->update(['step' => 'ESPERANDO_CONTRATO']);
                }
                elseif ($texto === 'menu_asesor') {
                    $this->enviarMensaje($numero, "Un asesor humano revisará tu caso pronto.");
                    $session->update(['step' => 'START']);
                }
                elseif ($texto === 'menu_estado') {
                    $this->enviarMensaje($numero, "Tu estado es: ACTIVO.");
                    // Podemos enviar botones para regresar
                    $this->enviarBotones($numero, "¿Deseas hacer algo más?", [
                        'btn_volver' => 'Volver al Menú'
                    ]);
                }
                elseif ($texto === 'btn_volver') { // Si viene del botón volver
                    $session->update(['step' => 'START']);
                    $this->manejarFlujo($session, ''); // Recursivo para mostrar menú
                }
                else {
                    // Si escriben algo que no es del menú
                    $this->enviarMensaje($numero, "Por favor utiliza el botón del menú para seleccionar una opción.");
                }
                break;

            case 'ESPERANDO_CONTRATO':
                if (is_numeric($texto)) {
                    $session->update(['temp_data' => ['contrato' => $texto], 'step' => 'CONFIRMACION']);

                    // USAMOS BOTONES PARA CONFIRMAR (SÍ/NO)
                    $this->enviarBotones(
                        $numero,
                        "Recibimos el contrato: *$texto*. \n¿Es correcto?",
                        [
                            'btn_si' => '✅ Sí, correcta',
                            'btn_no' => '❌ No, corregir'
                        ]
                    );
                } else {
                    $this->enviarMensaje($numero, "Por favor escribe solo números.");
                }
                break;

            case 'CONFIRMACION':
                if ($texto === 'btn_si') {
                    $contrato = $session->temp_data['contrato'];
                    $this->enviarMensaje($numero, "¡Agendado exitosamente! 🎉");

                    $session->update(['step' => 'START']);
                } elseif ($texto === 'btn_no') {
                    $this->enviarMensaje($numero, "Entendido, escribe el contrato nuevamente.");
                    $session->update(['step' => 'ESPERANDO_CONTRATO']);
                } else {
                    $this->enviarMensaje($numero, "Por favor presiona uno de los botones.");
                }
                break;

            default:
                $session->update(['step' => 'START']);
                $this->enviarMensaje($numero, "Reiniciando sistema...");
                $this->manejarFlujo($session, '');
                break;
        }
    }

    public function enviarMensaje($para, $texto)
    {
        $response = Http::withToken($this->token)->post("https://graph.facebook.com/{$this->version}/{$this->phoneId}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $para,
            'type' => 'text',
            'text' => ['body' => $texto]
        ]);

        // Loguear errores si falla el envío
        if ($response->failed()) {
            Log::error('Error enviando WhatsApp: ' . $response->body());
        }
    }

    /**
     * Envía un menú tipo LISTA (Botón que despliega opciones)
     * Máximo 10 opciones.
     */
    public function enviarMenuLista($para, $textoCuerpo, $textoBoton, $opciones)
    {
        // Construimos las filas (rows) de la lista
        $rows = [];
        foreach ($opciones as $id => $titulo) {
            $rows[] = [
                'id' => $id,          // Lo que recibe tu código (ej: 'menu_agendar')
                'title' => substr($titulo, 0, 24) // WhatsApp limita el título a 24 caracteres
                // 'description' => 'Descripción opcional'
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $para,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $textoCuerpo],
                'action' => [
                    'button' => $textoBoton, // Texto del botón que abre la lista
                    'sections' => [
                        [
                            'title' => 'Opciones',
                            'rows' => $rows
                        ]
                    ]
                ]
            ]
        ];

        $this->enviarRaw($payload);
    }

    /**
     * Envía BOTONES visibles (Máximo 3 botones)
     */
    public function enviarBotones($para, $textoCuerpo, $botones)
    {
        $buttonsData = [];
        foreach ($botones as $id => $titulo) {
            $buttonsData[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $id,
                    'title' => $titulo
                ]
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $para,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $textoCuerpo],
                'action' => [
                    'buttons' => $buttonsData
                ]
            ]
        ];

        $this->enviarRaw($payload);
    }

    // Función auxiliar para no repetir el Http::post
    private function enviarRaw($payload)
    {
        $response = Http::withToken($this->token)
            ->post("https://graph.facebook.com/{$this->version}/{$this->phoneId}/messages", $payload);

        if ($response->failed()) {
            Log::error('Meta API Error: ' . $response->body());
        }
    }

    public function ValContrato($contrato){



    }
}
