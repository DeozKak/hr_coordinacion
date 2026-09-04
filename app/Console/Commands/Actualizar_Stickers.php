<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\TblInspCali;
use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Bitacoras\TblBitacoraFallida;
use App\Models\Stickers\TblInspectorSticker;
use App\Models\Stickers\TblStickerTipo;
use App\Models\Stickers\TblStickerActaSerial;
use Rmunate\Calendario\CalendarioColombia;

class Actualizar_Stickers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * php artisan app:actualizar_-stickers
     * (Se deja tal cual tu firma para no romper llamadas existentes)
     */
    protected $signature = 'app:actualizar_-stickers';

    /**
     * The console command description.
     */
    protected $description = 'Cron job: Actualiza y descuenta stickers a los inspectores según su producción diaria.';

    public function handle(): int
    {
        $hoy = Carbon::today();

        // Retroceder hasta el último día no festivo
        $ayer = clone $hoy;
        $ayer->subDay(); // Comenzar con el día anterior


        if (CalendarioColombia::date($hoy->toDateString())->isHoliday()) {
            return Command::SUCCESS;
        }

        while (CalendarioColombia::date($ayer->toDateString())->isHoliday()) {
            $ayer->subDay(); // Retroceder día a día hasta encontrar uno no festivo
        }

        $inicioRango = $ayer->copy(); // Último día no festivo encontrado
        $finRango = $hoy->copy()->subDay(); // Día anterior al actual

        Log::info('[stickers] Rango de días seleccionado.', [
            'inicio_rango' => $inicioRango->toDateString(),
            'fin_rango' => $finRango->toDateString(),
        ]);
        $arrayFallidas = [
            '.DIRECCION NO ENCONTRADA',
            '.PREDIO EN CONSTRUCCION',
            'APLAZADO POR EL USUARIO.',
            'CASA SOLA.',
            'CERTIFICADA POR OIA EXTERNO.',
            'MEDIDOR POR LITROS BORRADOS.',
            'MENOR DE EDAD.',
            'NO ESTA EL ENCARGADO.',
            'NOVEDAD BLOQUEANTE',
            'NOVEDAD BLOQUEANTE.',
            'PERDIDA',
            "SIN GESTION.",
            'PREDIO DESOCUPADO.',
            'USUARIO NO AUTORIZA.'
        ];
        // Reglas de descuento
        $AMARILLO = 'AMARILLOS';
        $ROJO = 'ROJOS';
        $SUSPENSION = 'SUSPENSION';
        $ISOMETRICOS = 'ISOMETRICOS';
        $VISITA = 'CONS DE VISITA';
        $ACTAS = 'ACTAS';
        // Ajusta el nombre del campo si tu tabla de tipos usa otro en vez de "nombre" (por ejemplo "NOMBRE")
        $tiposNecesarios = [$AMARILLO, $ROJO, $SUSPENSION, $ISOMETRICOS, $VISITA, $ACTAS];
        $tipos = TblStickerTipo::query()
            ->whereIn('nombre', $tiposNecesarios)
            ->pluck('id', 'nombre');

        foreach ($tiposNecesarios as $tn) {
            if (!isset($tipos[$tn])) {
                Log::warning("[stickers] Tipo de sticker no encontrado en tbl_sticker_tipos: {$tn}");
            }
        }

        // Se quita ->with() porque no existen esas relaciones en el modelo compartido
        $inspectores = TblInspCali::query()
            ->where('state', 1)
            ->get();

        if ($inspectores->isEmpty()) {
            Log::info('[stickers] No hay inspectores activos para procesar.');
            $this->info('No hay inspectores activos para procesar.');
            return Command::SUCCESS;
        }

        // Normalizador de textos de cierre
        $normalize = static function (?string $s): string {
            $s = (string)$s;
            $s = trim($s);
            $s = ltrim($s, '.');
            return mb_strtoupper($s, 'UTF-8');
        };

        // Claves normalizadas
        $C_CER = 'CERTIFICADA';
        $C_CER_NOV = 'CERTIFICADA CON NOVEDADES';
        $C_DEF_CRIT = 'INSPECCIONADA CON DEFECTO CRITICO VALLE';
        $C_DEF_NO_CRIT = 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE';
        // <<< CAMBIO: Se define el array de tipos de trabajo de línea matriz
        $tipos_linea_matriz = ['FI-29 revisión periódica línea matriz', 'FI-31 REVISIÓN NUEVA LINEA MATRIZ'];


        $procesados = 0;

        foreach ($inspectores as $inspector) {
            $conteoPapel = 0;
            // Usa el campo real del documento del inspector. Ajusta si en tu tabla se llama distinto (por ej. CEDULA)
            $documentoInspector = $inspector->cedula;
            if (empty($documentoInspector)) {
                Log::warning("[stickers] Inspector ID {$inspector->id} sin documento configurado. Saltando.");
                continue;
            }

            $rows = TblBitacoraContrato::query()
                ->select('RESULTADO_CIERRE', DB::raw('COUNT(*) as total'))
                ->whereBetween('created_at', [
                    $inicioRango->startOfDay(),
                    $finRango->endOfDay()
                ])
                ->where('CC_OPERARIO', $documentoInspector)
                ->whereNotIn('TIPO_TRABAJO', $tipos_linea_matriz)
                ->whereNotNull('RESULTADO_CIERRE')
                ->groupBy('RESULTADO_CIERRE')
                ->get();

            // <<< CAMBIO: Consulta 2: Solo para contar trabajos de Línea Matriz
            $conteoLineaMatriz = TblBitacoraContrato::query()
                ->whereBetween('created_at', [
                    $inicioRango->startOfDay(),
                    $finRango->endOfDay()
                ])
                ->where('CC_OPERARIO', $documentoInspector)
                ->whereIn('TIPO_TRABAJO', $tipos_linea_matriz) // Se usa whereIn para buscar estos tipos
                ->count(); // Usamos count() para obtener directamente el número total
            //Visitas Fallidas
            $conteoFallidas = TblBitacoraFallida::whereBetween('created_at', [
                $inicioRango->startOfDay(),
                $finRango->endOfDay()
            ])
                ->where('CC_OPERARIO', $documentoInspector)
                ->whereIn('RESULTADO_CIERRE', $arrayFallidas)
                ->count();

            $actas = TblStickerActaSerial::where('id_inspector', $inspector->id)->get();
            if (!$actas->isEmpty()) {
                foreach ($actas as $acta) {
                    $Bitacora = TblBitacoraContrato::query()
                        ->whereBetween('created_at', [
                            $inicioRango->startOfDay(),
                            $finRango->endOfDay()
                        ])
                        ->where('CC_OPERARIO', $documentoInspector)
                        ->where('No_ACTA', '=', 'P' . $acta->serial)
                        ->first();
                    if ($Bitacora) {
                        $acta->estado = 'utilizado';
                        $acta->save();
                        $conteoPapel++;
                    }
                }
            }


            $conteo = [
                $C_CER => 0,
                $C_CER_NOV => 0,
                $C_DEF_CRIT => 0,
                $C_DEF_NO_CRIT => 0,
            ];


            foreach ($rows as $r) {
                $key = $normalize($r->RESULTADO_CIERRE);
                if (isset($conteo[$key])) {
                    $conteo[$key] += (int)$r->total;
                }
            }

            $descuentoAmarillos = $conteo[$C_CER] + $conteo[$C_CER_NOV] + $conteo[$C_DEF_CRIT] + $conteo[$C_DEF_NO_CRIT];
            $descuentoRojos = $conteo[$C_CER] + $conteo[$C_CER_NOV] + $conteoLineaMatriz;
            $descuentoSuspension = $conteo[$C_DEF_CRIT];
            $descuentoIsometricos = $conteo[$C_CER] + $conteo[$C_CER_NOV] + $conteo[$C_DEF_CRIT] + $conteo[$C_DEF_NO_CRIT] + $conteoLineaMatriz;
            $descuentoVisitas = $conteoFallidas;
            $descuentoActas = $conteoPapel;

            if ($descuentoAmarillos === 0 && $descuentoRojos === 0 && $descuentoSuspension === 0 && $descuentoIsometricos === 0 && $descuentoVisitas === 0 && $descuentoActas === 0) {
                continue;
            }

            DB::transaction(function () use (
                $inspector,
                $tipos,
                $AMARILLO,
                $ROJO,
                $SUSPENSION,
                $ISOMETRICOS,
                $VISITA,
                $ACTAS,
                $descuentoAmarillos,
                $descuentoRojos,
                $descuentoSuspension,
                $descuentoIsometricos,
                $descuentoVisitas,
                $descuentoActas
            ) {
                // Restar usando los nombres y columnas reales del inventario:
                // - id_sticker_tipo
                // - cantidad_asignada
                $restar = function (string $nombreTipo, int $cantidad) use ($inspector, $tipos) {
                    if ($cantidad <= 0) {
                        return;
                    }

                    $tipoId = $tipos[$nombreTipo] ?? null;
                    if (!$tipoId) {
                        Log::warning("[stickers] No se pudo descontar: tipo '{$nombreTipo}' no existe. Inspector {$inspector->id}");
                        return;
                    }

                    $inv = TblInspectorSticker::query()->firstOrCreate(
                        [
                            'id_inspector' => $inspector->id,
                            'id_sticker_tipo' => $tipoId,
                        ],
                        [
                            'cantidad_asignada' => 0,
                        ]
                    );

                    $campoCantidad = 'cantidad_asignada';

                    $actual = (int)($inv->{$campoCantidad} ?? 0);
                    $nuevo = max(0, $actual - $cantidad);

                    $inv->{$campoCantidad} = $nuevo;
                    $inv->save();
                };

                $restar($AMARILLO, $descuentoAmarillos);
                $restar($ROJO, $descuentoRojos);
                $restar($SUSPENSION, $descuentoSuspension);
                $restar($ISOMETRICOS, $descuentoIsometricos);
                $restar($VISITA, $descuentoVisitas);
                $restar($ACTAS, $descuentoActas);
            });

            Log::info('[stickers] Descuentos aplicados', [
                'inspector_id' => $inspector->id,
                'documento' => $documentoInspector,
                'fecha' => $hoy->toDateString(),
                'conteos' => [
                    'CERTIFICADA' => $conteo[$C_CER],
                    'CERTIFICADA CON NOVEDADES' => $conteo[$C_CER_NOV],
                    'INSPECCIONADA CON DEFECTO CRITICO VALLE' => $conteo[$C_DEF_CRIT],
                    'INSPECCIONADA CON DEFECTO NO CRITICO VALLE' => $conteo[$C_DEF_NO_CRIT],
                    'LINEAS MATRICES' => $conteoLineaMatriz,
                    'FALLIDAS' => $conteoFallidas,
                    'ACTAS' => $conteoPapel,
                ],
                'descuentos' => [
                    'AMARILLOS' => $descuentoAmarillos,
                    'ROJOS' => $descuentoRojos,
                    'SUSPENSION' => $descuentoSuspension,
                    'ISOMETRICOS' => $descuentoIsometricos,
                    'VISITA' => $descuentoVisitas,
                    'ACTAS' => $descuentoActas,
                ],
            ]);

            $procesados++;
        }

        $this->info("Proceso finalizado. Inspectores procesados: {$procesados}.");
        return Command::SUCCESS;
    }
}
