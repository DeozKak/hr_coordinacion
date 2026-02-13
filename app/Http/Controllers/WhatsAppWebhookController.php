<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\WhatsAppBotService;
use Illuminate\Support\Facades\Log;
class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, WhatsAppBotService $botService)
    {

        // 1. Manejo de VERIFICACIÓN (GET Request)
        // Según docs: "GET requests are used to verify your webhook endpoint"
        if ($request->isMethod('get'))
        {

            $verifyToken = env('META_WHATSAPP_VERIFY_TOKEN');

            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');
            //return $token;
            if ($mode === 'subscribe' && $token === $verifyToken) {

                // Respondemos con el challenge y status 200 como pide Meta
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            // Si el token no coincide, prohibido
            return response('Forbidden', 403);
        }

        // 2. Manejo de EVENTOS (POST Request)
        // Según docs: "POST requests... containing a JSON payload describing the event"
        if ($request->isMethod('post')) {

            // VALIDACIÓN DE SEGURIDAD (HMAC-SHA256)
            // Según docs: "Validate the request... Generate an HMAC-SHA256 hash"
            $signature = $request->header('X-Hub-Signature-256');

            if (!$this->isValidSignature($request->getContent(), $signature)) {
                Log::warning('Intento de webhook con firma inválida');
                return response('Invalid Signature', 403);
            }

            // Capturar el payload
            $body = $request->all();

            // Verificar estructura básica de WhatsApp
            if (isset($body['entry'][0]['changes'][0]['value']['messages'][0])) {

                $message = $body['entry'][0]['changes'][0]['value']['messages'][0];
                $from = $message['from'];
                $type = $message['type'];
                $text = '';

                // Extraer texto según el tipo
                if ($type === 'text') {
                    $text = $message['text']['body'];
                }
                elseif ($type === 'interactive') {
                    $interactive = $message['interactive'];

                    // CASO A: El usuario tocó un BOTÓN (Reply Button)
                    if (isset($interactive['button_reply'])) {
                        $text = $interactive['button_reply']['id']; // Usaremos el ID para la lógica
                    }
                    // CASO B: El usuario seleccionó de una LISTA
                    elseif (isset($interactive['list_reply'])) {
                        $text = $interactive['list_reply']['id']; // Usaremos el ID para la lógica
                    }
                }

                // --- AQUÍ OCURRE LA MAGIA ---
                // Le pasamos la pelota al servicio y nos olvidamos
                if (!empty($text)) {
                    $botService->procesarMensaje($from, $text);
                }
            }


            // Importante: Responder 200 OK inmediatamente para evitar reintentos de Meta
            return response('EVENT_RECEIVED', 200);
        }
    }

    /**
     * Valida la firma SHA256 proporcionada por Meta
     */
    private function isValidSignature($payload, $signature)
    {
        if (!$signature) {
            return false;
        }

        $appSecret = env('META_WHATSAPP_APP_SECRET');

        // La firma viene como "sha256=hash...", quitamos el prefijo
        $expectedHash = hash_hmac('sha256', $payload, $appSecret);
        $signatureHash = str_replace('sha256=', '', $signature);

        return hash_equals($expectedHash, $signatureHash);
    }


    private function enviarMensaje($para, $mensaje)
    {
        $token = env('META_WHATSAPP_TOKEN'); // Asegúrate de tener esto en .env
        $phoneId = '936199389570659'; // Tu ID
        $version = 'v21.0';

        Http::withToken($token)->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $para,
            'type' => 'text',
            'text' => ['body' => $mensaje]
        ]);
    }
}
