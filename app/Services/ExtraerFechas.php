<?php /** @noinspection ALL */

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Log;

class ExtraerFechas
{


    /**
     * @return void
     * función dedicada a extraer fechas del campo de observaciones de OSF de GDO
     */
    public function findDates(string $texto, ?string $fechaDeReferencia = null, $id_reg)
    {
        date_default_timezone_set('America/Bogota');
        // REGLAS de limpieza previas (sin cambios)
        $texto = str_ireplace('LA MAÑANA', '', $texto);
        $texto = preg_replace('/FECHA MAX\s+\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}/i', '', $texto);
        $texto = preg_replace('/\\((FS|FA):\\s*.*?\\)/i', '', $texto);

        $ahora = $fechaDeReferencia ? new DateTime($fechaDeReferencia) : new DateTime();
        $timestampDeReferencia = $ahora->getTimestamp();

        // <-- CAMBIO 1: ESTRATEGIA DE MÚLTIPLES PATRONES -->
        // En lugar de un patrón gigante, creamos un arreglo de patrones individuales.
        $patrones = [
            // Patrón para fechas numéricas completas (yyyy-mm-dd, dd/mm/yyyy, etc.)
            '/(?:\d{1,4}\s*[\/\-]\s*\d{1,2}\s*[\/\-]\s*\d{1,4})/iu',
            // Patrón para fechas con nombre de mes (11 de Junio de 2025)
            '/(?:(?:lunes|martes|miercoles|jueves|viernes|sabado|domingo)\s*,?\s*)?(?:\d{1,2})\s+(?:de\s+)?(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)(?:\s*,?\s*(?:de\s+)?\d{2,4})?/iu',
            // NUEVO: Patrón para "Mes Día" (ej. Junio 17)
            '/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+(0?[1-9]|[12]\d|3[01])(?:,?\s+\d{2,4})?\b/iu',
            // Patrón para "nombre de día + número" (Miércoles 11)
            '/(?:(?:lunes|martes|miercoles|jueves|viernes|sabado|domingo)\s+\d{1,2})\b/iu',
            // Patrón para "número + nombre de mes" (14/junio)
            '/(?:\d{1,2}\s*[\/\-]\s*(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre))/iu',
            // Patrón para "día/mes" (11/06) con validación estricta
            '/(?:(0?[1-9]|[12]\d|3[01])\s*[\/\-]\s*(0?[1-9]|1[0-2]))\b/iu',
            // Patrón para palabras clave relativas
            '/\b(?:mañana|hoy|ayer|lunes|martes|miercoles|jueves|viernes|sabado|domingo)\b/iu'
        ];

        $todasLasCoincidencias = [];
        foreach ($patrones as $patron) {
            if (preg_match_all($patron, $texto, $coincidencias)) {
                $todasLasCoincidencias = array_merge($todasLasCoincidencias, $coincidencias[0]);
            }
        }

        // Eliminar duplicados exactos que puedan surgir de las múltiples búsquedas
        $coincidencias[0] = array_unique($todasLasCoincidencias);
        // <-- FIN DEL CAMBIO DE ESTRATEGIA -->

        $fechasEncontradas = [];

        if (!empty($coincidencias[0])) {

            $terminosRelativos = [
                'hoy' => 'today', 'mañana' => 'tomorrow', 'ayer' => 'yesterday',
                'lunes' => 'next Monday', 'martes' => 'next Tuesday', 'miercoles' => 'next Wednesday',
                'jueves' => 'next Thursday', 'viernes' => 'next Friday', 'sabado' => 'next Saturday',
                'domingo' => 'next Sunday'
            ];

            // Lógica de prioridad (sin cambios)
            $fechasExplicitas = [];
            $palabrasClaveRelativas = [];

            foreach ($coincidencias[0] as $match) {
                if (preg_match('/\\d/', $match)) {
                    $fechasExplicitas[] = $match;
                } else if (isset($terminosRelativos[mb_strtolower($match, 'UTF-8')])) {
                    $palabrasClaveRelativas[] = $match;
                }
            }

            $listaAProcesar = !empty($fechasExplicitas) ? $fechasExplicitas : $palabrasClaveRelativas;

            $meses_es = ['enero' => 'jan', 'febrero' => 'feb', 'marzo' => 'mar', 'abril' => 'apr', 'mayo' => 'may', 'junio' => 'jun', 'julio' => 'jul', 'agosto' => 'aug', 'septiembre' => 'sep', 'octubre' => 'oct', 'noviembre' => 'nov', 'diciembre' => 'dec'];
            // Corregí tu mapeo de días para que sea a inglés y funcione con strtotime
            $dias_es = ['lunes' => 'monday', 'martes' => 'tuesday', 'miercoles' => 'wednesday', 'jueves' => 'thursday', 'viernes' => 'friday', 'sabado' => 'saturday', 'domingo' => 'sunday'];

            foreach ($listaAProcesar as $posibleFecha) {
                try {
                    // Tu lógica de procesamiento (sin cambios)
                    $textoAProcesar = mb_strtolower(str_replace(',', '', $posibleFecha), 'UTF-8');
                    $fechaObj = null;

                    if (isset($terminosRelativos[$textoAProcesar])) {
                        try {
                            $fechaObj = new DateTime(date('Y-m-d H:i:s', strtotime($terminosRelativos[$textoAProcesar], $timestampDeReferencia)));
                        } catch (\Exception $e) {
                             log::error($e->getMessage() . " " . $texto);
                            return 1000;
                        }
                    } else {
                        // Reemplazar nombres de días en español por inglés antes de procesar
                        $textoAProcesar = str_ireplace(array_keys($dias_es), array_values($dias_es), $textoAProcesar);

                        $textoAProcesar = str_replace('/', '-', $textoAProcesar);
                        $textoAProcesar = str_replace(' de ', ' ', $textoAProcesar);
                        $textoAProcesar = strtr($textoAProcesar, $meses_es);
                        $textoAProcesar = trim($textoAProcesar);
                        $textoAProcesar = preg_replace('/\s*-\s*/', '-', $textoAProcesar);

                     /*   if ($id_reg == 105) {
                            // Ahora dd() mostrará ambas coincidencias
                            dd($textoAProcesar);
                        }*/
                        if (!empty($textoAProcesar)) {
                            try {
                                $fechaObj = new DateTime(date('Y-m-d H:i:s', strtotime($textoAProcesar, $timestampDeReferencia)));
                            } catch (\Exception $e) {
                                 log::error($e->getMessage() . " " . $texto);
                                return 1000;
                            }
                        }
                    }

                    if ($fechaObj) {
                        $fechasEncontradas[] = $fechaObj;
                    }
                } catch (\Exception $e) {
                     log::error($e->getMessage() . " " . $texto);
                    return 1000;
                }
            }
        }

        $fechasUnicas = [];
        $mapaFechas = [];
        foreach ($fechasEncontradas as $fecha) {
            $key = $fecha->format('Y-m-d');
            if (!isset($mapaFechas[$key])) {
                $mapaFechas[$key] = true;
                $fechasUnicas[] = $fecha;
            }
        }

        // Si hay dos o más fechas, eliminamos la fecha indeseada
        if (count($fechasUnicas) >= 2) {
            $fechasUnicas = array_filter($fechasUnicas, function($fecha) {
                return $fecha->format('Y-m-d H:i:s') !== '1969-12-31 19:00:00';
            });
            // Reindexa el array por si necesitas índices consecutivos
            $fechasUnicas = array_values($fechasUnicas);
        }


        return $fechasUnicas;
    }
}
