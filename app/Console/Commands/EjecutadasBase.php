<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Movilidad;

class EjecutadasBase extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ejecutadas-base';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el estado EJECUTADA en tbl_programacion_base según bitácoras del día';
    /**
     * Execute the console command.
     */
    public function handle()
    {

        $hoy = Carbon::today();

        $this->info("Iniciando actualización para la fecha: " . $hoy->toDateString());

        $bitacoraDiaria = DB::table('tbl_bitacora_diaria')
            ->whereDate('created_at', $hoy)
            ->select('ORDEN_TRABAJO', 'CONTRATO','TIPO_TRABAJO','FECHA','ORDEN_EXT')
            ->get();

        $bitacoraContratos = DB::table('tbl_bitacora_contratos')
            ->whereDate('created_at', $hoy)
            ->select('ORDEN_TRABAJO', 'CONTRATO','TIPO_TRABAJO','FECHA','ORDEN_EXT')
            ->get();


        $registrosNuevos = $bitacoraDiaria->concat($bitacoraContratos);

        if ($registrosNuevos->isEmpty()) {
            $this->warn("No se encontraron registros nuevos hoy.");
            return;
        }

        $contador = 0;

        foreach ($registrosNuevos as $registro) {

            if($registro->TIPO_TRABAJO === 'RP 12161'){
                //DD($registro);
                $orden = $registro->ORDEN_EXT;
            }else{
                $orden = $registro->ORDEN_TRABAJO;
            }
            $contrato = ltrim($registro->CONTRATO, ':');
            $actualizado = DB::table('tbl_programacion_base')
                ->where('NUMERO_ORDEN',$orden)
                ->where('CONTRATO', $contrato)
                ->where('ESTADO_RECEPCION', '!=', 1) // Solo si no está ya marcada
                ->update(['ESTADO_RECEPCION' => 1]);

            if ($actualizado) {
                $contador++;
            }
        }

        $this->info("Proceso terminado. Se actualizaron {$contador} registros.");
    }
}
