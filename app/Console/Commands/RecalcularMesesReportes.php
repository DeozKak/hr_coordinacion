<?php

namespace App\Console\Commands;

use App\Services\Home\MesesDeVencimientoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rehace la columna Meses de reportes_diarios con la cuenta buena.
 *
 * Las filas ya guardadas conservan el valor que tenían cuando se sincronizaron,
 * que salía de tbl_programacion_base.MESES —con valores sin recalcular, hasta
 * 1.580— o de FECHA_ULTCERTI. Este comando las vuelve a calcular desde el plazo
 * máximo de la orden.
 *
 * Solo alcanza a los contratos que sigan en tbl_asignaciones: una orden ya
 * cerrada desaparece de esa foto y no hay plazo con el que recalcular, así que
 * esas filas se dejan como están.
 */
class RecalcularMesesReportes extends Command
{
    protected $signature = 'reportes:recalcular-meses {--aplicar : Escribe los cambios; sin esta opción solo informa}';

    protected $description = 'Recalcula los meses de vencimiento de reportes_diarios desde PLAZO_MAXIMO';

    public function handle(MesesDeVencimientoService $meses): int
    {
        $aplicar = (bool) $this->option('aplicar');

        $filas = DB::table('reportes_diarios')->select('id', 'NroSitio', 'Meses')->get();

        $contratos = $filas->map(fn ($f) => ltrim((string) $f->NroSitio, ':'))
            ->filter()->unique()->values()->all();

        $porContrato = $meses->porContrato($contratos);

        $porValor = [];
        $cambios = 0;
        $sinPlazo = 0;

        foreach ($filas as $fila) {
            $contrato = ltrim((string) $fila->NroSitio, ':');

            if (! array_key_exists($contrato, $porContrato)) {
                $sinPlazo++;
                continue;
            }

            $nuevo = $porContrato[$contrato];

            if ((string) $fila->Meses === (string) $nuevo) {
                continue;
            }

            /* Se agrupa aquí y no con el id de clave: los id de esta tabla son
               cadenas, y PHP convierte a entero toda clave que parezca número.
               Al llegar al whereIn iban sin comillas y MySQL intentaba comparar
               la columna varchar como número. */
            $porValor[$nuevo][] = (string) $fila->id;
            $cambios++;
        }

        $this->info(sprintf(
            '%d filas revisadas · %d sin orden abierta con la que recalcular · %d cambian',
            $filas->count(), $sinPlazo, $cambios
        ));

        if ($cambios === 0) {
            return self::SUCCESS;
        }

        if (! $aplicar) {
            $this->warn('Simulación: no se escribió nada. Repite con --aplicar.');

            return self::SUCCESS;
        }

        // Un UPDATE por valor y no uno por fila: son pocos valores distintos.
        DB::transaction(function () use ($porValor) {
            foreach ($porValor as $valor => $ids) {
                foreach (array_chunk($ids, 500) as $tanda) {
                    DB::table('reportes_diarios')
                        ->whereIn('id', $tanda)
                        ->update(['Meses' => (int) $valor]);
                }
            }
        });

        $this->info($cambios . ' filas actualizadas.');

        return self::SUCCESS;
    }
}
