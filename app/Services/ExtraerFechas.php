<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Log;
use OpenAI;

class ExtraerFechas
{
    protected $client;

    public function __construct()
    {
        $apiKey = config('services.groq.key');
        // Inicializamos el cliente apuntando a Groq
        $this->client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri('https://api.groq.com/openai/v1')
            ->make();
    }

    /**
     * @return array
     * Función dedicada a extraer fechas y jornada 100% con IA (Llama 3.1)
     */
    public function findDates(string $texto, ?string $fechaDeReferencia = null, $id_reg = null)
    {
        date_default_timezone_set('America/Bogota');

        // ====================================================================
        // 1. REGLAS DE LIMPIEZA ANTIALUCINACIONES (VITALES PARA LA IA)
        // ====================================================================
        $textoLimpio = preg_replace('/FECHA MAX\s+\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}/i', '', $texto);
        $textoLimpio = preg_replace('/\\((FS|FA):\\s*.*?\\)/i', '', $textoLimpio);

        // Borramos todo lo que confunde a la IA
        $textoLimpio = preg_replace('/\b3\d{9}\b/', '', $textoLimpio); // Celulares
        $textoLimpio = preg_replace('/\$[0-9.,]+/', '', $textoLimpio); // Precios ($138900)
        $textoLimpio = preg_replace('/\b\d{1,2}\s*A\s*\d{1,2}\s*(DH|DIAS HABILES|MESES|DIAS)\b/i', '', $textoLimpio); // Rangos
        $textoLimpio = preg_replace('/\b\d{1,2}\s*(DH|DIAS HABILES|MESES|DIAS)\b/i', '', $textoLimpio); // Tiempos exactos
        // 🟢 NUEVO: Borramos CUALQUIER secuencia de 5 o más números (Precios sin $, Cédulas, Celulares, Contratos)
        $textoLimpio = preg_replace('/\b\d{5,}\b/', '', $textoLimpio);
        // 🟢 MAGIA DINÁMICA: Voltea CUALQUIER fecha DD/MM/YYYY a YYYY-MM-DD
        $textoLimpio = preg_replace('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/', '$3-$2-$1', $textoLimpio);

        // 🟢 MAGIA DINÁMICA MEJORADA: Voltea fechas y detecta años de 2 dígitos inteligentemente
        $textoLimpio = preg_replace_callback('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2}|\d{4})\b/', function($matches) {
            $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);

            if (strlen($matches[3]) === 4) {
                // Formato clásico con año de 4 dígitos (ej. 12/05/2026)
                $anio = $matches[3];
                $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            } else {
                // Formato con año de 2 dígitos (ej. 26/05/27 o 27/05/26)
                $val1 = (int)$matches[1];
                $val3 = (int)$matches[3];
                $anioActual = (int)date('y'); // Extrae automáticamente '26' (del 2026)


                if ($val1 === $anioActual && $val3 !== $anioActual) {
                    $anio = '20' . $val1;
                    $dia = str_pad($val3, 2, '0', STR_PAD_LEFT);
                }

                elseif ($val3 === $anioActual && $val1 !== $anioActual) {
                    $anio = '20' . $val3;
                    $dia = str_pad($val1, 2, '0', STR_PAD_LEFT);
                }

                else {
                    $anio = '20' . str_pad($val3, 2, '0', STR_PAD_LEFT);
                    $dia = str_pad($val1, 2, '0', STR_PAD_LEFT);
                }
            }

            return $anio . '-' . $mes . '-' . $dia;
        }, $textoLimpio);
        // ====================================================================
        // 2. VALIDACIÓN DE SEGURIDAD: ¿QUEDAN NÚMEROS?
        // ====================================================================
        // Si después de limpiar celulares y precios no quedan dígitos,
        // asumimos que no hay fecha explícita y evitamos la llamada a la IA.
        if (!preg_match('/\d/', $textoLimpio)) {
            return [
                'fechas' => [],
                'jornada' => null
            ];
        }

        // ====================================================================
        // 3. PROCESAMIENTO EXCLUSIVO CON IA (GROQ)
        // ====================================================================
        try {
            // Prompt actualizado con reglas estrictas para formato latinoamericano y jornadas
            $promptSistema = "Extrae SOLO fechas explícitas de agendamiento y jornada (MAÑANA/TARDE/TODO EL DIA). " .
                "La fecha me lo entregas en el siguiente formato YYYY-MM-DD".
                "Dar prioridad a la fecha que esta en seguida de la palabra FECHA DE VISITA o FECHA SUGERIDA".
                "REGLA VITAL: Si el texto NO contiene una fecha de visita, retorna ESTRICTAMENTE {\"fechas\": [], \"jornada\": null}. " .
                "IGNORA números sueltos y no asumas fechas. " .
                "Regla jornada: Cualquier hora con 'AM' = mañana; Cualquier hora con 'PM' = tarde. Retorna SOLO JSON.";

            $response = $this->client->chat()->create([
                'model' => 'openai/gpt-oss-20b',
                'messages' => [
                    ['role' => 'system', 'content' => $promptSistema],
                    ['role' => 'user', 'content' => $textoLimpio],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.0 // 100% robótico, nada de inventos
            ]);

            $resultado = json_decode($response->choices[0]->message->content, true);
            $fechasExtraidas = $resultado['fechas'] ?? [];
            $jornadaExtraida = $resultado['jornada'] ?? null;

            $fechasUnicas = [];
            $mapaFechas = [];

            // Convertimos los strings a objetos DateTime
            foreach ($fechasExtraidas as $fechaStr) {
                try {
                    $fechaObj = new DateTime($fechaStr);
                    $key = $fechaObj->format('Y-m-d');

                    if (!isset($mapaFechas[$key])) {
                        $mapaFechas[$key] = true;
                        $fechasUnicas[] = $fechaObj;
                    }
                } catch (\Exception $e) {
                    Log::error("Error parseando fecha de IA: " . $fechaStr);
                }
            }

            return [
                'fechas' => $fechasUnicas,
                'jornada' => $jornadaExtraida
            ];

        } catch (\Exception $e) {
            // Si la API se cae o da error de rate limit, devuelve vacío para no romper el Excel
            Log::error("Error Groq API: " . $e->getMessage() . " | ID_REG: " . $id_reg . " | Texto: " . $textoLimpio);
            return [
                'fechas' => [],
                'jornada' => null
            ];
        }
    }
}
