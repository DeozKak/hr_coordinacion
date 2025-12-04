<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {

        // 1. Manejo de VERIFICACIÓN (GET Request)
        // Según docs: "GET requests are used to verify your webhook endpoint"
        if ($request->isMethod('get'))
        {

            $verifyToken = env('META_WHATSAPP_VERIFY_TOKEN');

            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

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
            $data = $request->all();

            // LOGICA DE TU NEGOCIO
            // Aquí procesas el mensaje (ej: leer el mensaje, guardarlo en BD, disparar respuesta)
            Log::info('Webhook recibido:', $data);

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
}
