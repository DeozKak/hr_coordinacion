<?php

namespace App\Console\Commands;

use App\Models\Programacion\TblProgramacionContrato;
use App\Services\ProgramacionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EjecutadasProgramacion extends Command
{
    protected $signature = 'app:ejecutadas-programacion';

    protected $description = 'Marca como ejecutadas las programaciones que ya se cerraron en campo';

    /**
     * Una ejecución más vieja que esto no cierra la programación de hoy.
     *
     * La bitácora guarda años de historia y un contrato se revisa cada tanto,
     * así que sin este límite una certificación antigua daría por hecha una
     * programación recién hecha.
     */
    private const ANOS_DE_VIGENCIA = 2;

    /**
     * Recorre lo pendiente y pregunta al servicio si ya se ejecutó.
     *
     * La lógica de qué cuenta como ejecutado —los códigos equivalentes, los
     * cierres que valen, la excepción del SA 12164 y de qué tablas salen— vive
     * en findExecuted y la comparte con la programación manual y las cargas
     * masivas. Aquí solo queda lo propio del comando: a quién preguntar, desde
     * cuándo vale la respuesta y qué hacer con ella.
     */
    public function handle(ProgramacionService $ejecutados)
    {
        $limite = Carbon::now()->subYears(self::ANOS_DE_VIGENCIA)->toDateString();
        $marcadas = 0;

        $pendientes = TblProgramacionContrato::where('EJECUTADA', 0)
            ->where('FECHA_AGENDAMIENTO', '>=', date('Y-m-d'))
            ->get();

        foreach ($pendientes as $programada) {
            $ejecutado = $ejecutados->findExecuted(
                $programada->CONTRATO, $programada->TIPO_TRABAJO, $programada->ORDEN_TRABAJO
            );

            if ($ejecutado === null || $this->fecha($ejecutado->FECHA) <= $limite) {
                continue;
            }

            $programada->EJECUTADA = 1;
            $programada->save();
            $marcadas++;
        }

        $this->info("Revisadas {$pendientes->count()} programaciones, {$marcadas} marcadas como ejecutadas.");

        return self::SUCCESS;
    }

    /**
     * El día de la ejecución.
     *
     * La bitácora la devuelve como fecha y movilidad como fecha y hora, así que
     * se corta por el espacio y sirven las dos.
     */
    private function fecha($valor): string
    {
        return explode(' ', (string) $valor)[0];
    }
}
