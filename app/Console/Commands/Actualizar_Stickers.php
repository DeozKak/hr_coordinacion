<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\tbl_insp_cali;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use App\Models\Stickers\tbl_inspector_sticker;
use App\Models\Stickers\tbl_sticker_tipo;

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
        $ayer = clone $hoy;
        $ayer->subDay();

        // Ajusta el nombre del campo si tu tabla de tipos usa otro en vez de "nombre" (por ejemplo "NOMBRE")
        $tiposNecesarios = ['ROJOS', 'AMARILLOS', 'SUSPENSION'];
        $tipos = tbl_sticker_tipo::query()
            ->whereIn('nombre', $tiposNecesarios)
            ->pluck('id', 'nombre');

        foreach ($tiposNecesarios as $tn) {
            if (!isset($tipos[$tn])) {
                Log::warning("[Stickers] Tipo de sticker no encontrado en tbl_sticker_tipos: {$tn}");
            }
        }

        // Se quita ->with() porque no existen esas relaciones en el modelo compartido
        $inspectores = tbl_insp_cali::query()
            ->where('state', 1)
            ->get();

        if ($inspectores->isEmpty()) {
            Log::info('[Stickers] No hay inspectores activos para procesar.');
            $this->info('No hay inspectores activos para procesar.');
            return Command::SUCCESS;
        }

        // Normalizador de textos de cierre
        $normalize = static function (?string $s): string {
            $s = (string) $s;
            $s = trim($s);
            $s = ltrim($s, '.');
            return mb_strtoupper($s, 'UTF-8');
        };

        // Claves normalizadas
        $C_CER         = 'CERTIFICADA';
        $C_CER_NOV     = 'CERTIFICADA CON NOVEDADES';
        $C_DEF_CRIT    = 'INSPECCIONADA CON DEFECTO CRITICO VALLE';
        $C_DEF_NO_CRIT = 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE';

        // Reglas de descuento
        $AMARILLO   = 'AMARILLOS';
        $ROJO       = 'ROJOS';
        $SUSPENSION = 'SUSPENSION';

        $procesados = 0;

        foreach ($inspectores as $inspector) {
            // Usa el campo real del documento del inspector. Ajusta si en tu tabla se llama distinto (por ej. CEDULA)
            $documentoInspector = $inspector->CC ?? $inspector->cedula ?? $inspector->documento ?? null;

            if (empty($documentoInspector)) {
                Log::warning("[Stickers] Inspector ID {$inspector->id} sin documento configurado. Saltando.");
                continue;
            }

            $rows = tbl_bitacora_contrato::query()
                ->select('RESULTADO_CIERRE', DB::raw('COUNT(*) as total'))
                ->whereDate('FECHA', $ayer->toDateString())
                ->where('CC_OPERARIO', $documentoInspector)
                ->whereNotNull('RESULTADO_CIERRE')
                ->groupBy('RESULTADO_CIERRE')
                ->get();


            $conteo = [
                $C_CER          => 0,
                $C_CER_NOV      => 0,
                $C_DEF_CRIT     => 0,
                $C_DEF_NO_CRIT  => 0,
            ];


            foreach ($rows as $r) {
                $key = $normalize($r->RESULTADO_CIERRE);
                if (isset($conteo[$key])) {
                    $conteo[$key] += (int) $r->total;
                }
            }

            $descuentoAmarillos  = $conteo[$C_CER] + $conteo[$C_CER_NOV] + $conteo[$C_DEF_CRIT] + $conteo[$C_DEF_NO_CRIT];
            $descuentoRojos      = $conteo[$C_CER] + $conteo[$C_CER_NOV];
            $descuentoSuspension = $conteo[$C_DEF_CRIT];

            if ($descuentoAmarillos === 0 && $descuentoRojos === 0 && $descuentoSuspension === 0) {
                continue;
            }

            DB::transaction(function () use (
                $inspector,
                $tipos,
                $AMARILLO,
                $ROJO,
                $SUSPENSION,
                $descuentoAmarillos,
                $descuentoRojos,
                $descuentoSuspension
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
                        Log::warning("[Stickers] No se pudo descontar: tipo '{$nombreTipo}' no existe. Inspector {$inspector->id}");
                        return;
                    }

                    $inv = tbl_inspector_sticker::query()->firstOrCreate(
                        [
                            'id_inspector'    => $inspector->id,
                            'id_sticker_tipo' => $tipoId,
                        ],
                        [
                            'cantidad_asignada' => 0,
                        ]
                    );

                    $campoCantidad = 'cantidad_asignada';

                    $actual = (int) ($inv->{$campoCantidad} ?? 0);
                    $nuevo  = max(0, $actual - $cantidad);

                    $inv->{$campoCantidad} = $nuevo;
                    $inv->save();
                };

                $restar($AMARILLO, $descuentoAmarillos);
                $restar($ROJO, $descuentoRojos);
                $restar($SUSPENSION, $descuentoSuspension);
            });

            Log::info('[Stickers] Descuentos aplicados', [
                'inspector_id' => $inspector->id,
                'documento'    => $documentoInspector,
                'fecha'        => $hoy->toDateString(),
                'conteos'      => [
                    'CERTIFICADA'                                 => $conteo[$C_CER],
                    'CERTIFICADA CON NOVEDADES'                   => $conteo[$C_CER_NOV],
                    'INSPECCIONADA CON DEFECTO CRITICO VALLE'     => $conteo[$C_DEF_CRIT],
                    'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'  => $conteo[$C_DEF_NO_CRIT],
                ],
                'descuentos'   => [
                    'AMARILLOS'  => $descuentoAmarillos,
                    'ROJOS'      => $descuentoRojos,
                    'SUSPENSION' => $descuentoSuspension,
                ],
            ]);

            $procesados++;
        }

        $this->info("Proceso finalizado. Inspectores procesados: {$procesados}.");
        return Command::SUCCESS;
    }
}
