<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\tbl_insp_cali;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use App\Models\Bitacoras\tbl_bitacora_fallida;
use App\Models\Stickers\tbl_inspector_sticker;
use App\Models\Stickers\tbl_sticker_tipo;
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


        if(CalendarioColombia::date($hoy->toDateString())->isHoliday()){
            return Command::SUCCESS;
        }

        while (CalendarioColombia::date($ayer->toDateString())->isHoliday()) {
            $ayer->subDay(); // Retroceder día a día hasta encontrar uno no festivo
        }

        $inicioRango = $ayer->copy(); // Último día no festivo encontrado
        $finRango = $hoy->copy()->subDay(); // Día anterior al actual

        Log::info('[Stickers] Rango de días seleccionado.', [
            'inicio_rango' => $inicioRango->toDateString(),
            'fin_rango'    => $finRango->toDateString(),
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
        $AMARILLO   = 'AMARILLOS';
        $ROJO       = 'ROJOS';
        $SUSPENSION = 'SUSPENSION';
        $ISOMETRICOS = 'ISOMETRICOS';
        $VISITA = 'CONS DE VISITA';
        // Ajusta el nombre del campo si tu tabla de tipos usa otro en vez de "nombre" (por ejemplo "NOMBRE")
        $tiposNecesarios = [$AMARILLO, $ROJO, $SUSPENSION, $ISOMETRICOS ,$VISITA];
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
        // <<< CAMBIO: Se define el array de tipos de trabajo de línea matriz
        $tipos_linea_matriz = ['FI-29 revisión periódica línea matriz', 'FI-31 REVISIÓN NUEVA LINEA MATRIZ'];



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
                ->whereBetween('FECHA', [$inicioRango->toDateString(), $finRango->toDateString()])
                ->where('CC_OPERARIO', $documentoInspector)
                ->whereNotIn('TIPO_TRABAJO', $tipos_linea_matriz)
                ->whereNotNull('RESULTADO_CIERRE')
                ->groupBy('RESULTADO_CIERRE')
                ->get();

            // <<< CAMBIO: Consulta 2: Solo para contar trabajos de Línea Matriz
            $conteoLineaMatriz = tbl_bitacora_contrato::query()
                ->whereBetween('FECHA', [$inicioRango->toDateString(), $finRango->toDateString()])
                ->where('CC_OPERARIO', $documentoInspector)
                ->whereIn('TIPO_TRABAJO', $tipos_linea_matriz) // Se usa whereIn para buscar estos tipos
                ->count(); // Usamos count() para obtener directamente el número total
            //Visitas Fallidas
            $conteoFallidas = tbl_bitacora_fallida::whereBetween('FECHA', [$inicioRango->toDateString(), $finRango->toDateString()])
            ->where('CC_OPERARIO', $documentoInspector)
            ->whereIn('TIPO_TRABAJO', $arrayFallidas)
            ->count();

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
            $descuentoRojos      = $conteo[$C_CER] + $conteo[$C_CER_NOV] + $conteoLineaMatriz;
            $descuentoSuspension = $conteo[$C_DEF_CRIT];
            $descuentoIsometricos = $conteo[$C_CER] + $conteo[$C_CER_NOV] + $conteo[$C_DEF_CRIT] + $conteo[$C_DEF_NO_CRIT] + $conteoLineaMatriz;
            $descuentoVisitas = $conteoFallidas;

            if ($descuentoAmarillos === 0 && $descuentoRojos === 0 && $descuentoSuspension === 0 && $descuentoIsometricos === 0 && $descuentoVisitas === 0) {
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
                $descuentoAmarillos,
                $descuentoRojos,
                $descuentoSuspension,
                $descuentoIsometricos,
                $descuentoVisitas
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
                $restar($ISOMETRICOS, $descuentoIsometricos);
                $restar($VISITA, $descuentoVisitas);
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
                    'LINEAS MATRICES'                             => $conteoLineaMatriz,
                    'FALLIDAS'                                    => $conteoFallidas,
                ],
                'descuentos'   => [
                    'AMARILLOS'  => $descuentoAmarillos,
                    'ROJOS'      => $descuentoRojos,
                    'SUSPENSION' => $descuentoSuspension,
                    'ISOMETRICOS' => $descuentoIsometricos,
                    'VISITA'     => $descuentoVisitas,
                ],
            ]);

            $procesados++;
        }

        $this->info("Proceso finalizado. Inspectores procesados: {$procesados}.");
        return Command::SUCCESS;
    }
}
