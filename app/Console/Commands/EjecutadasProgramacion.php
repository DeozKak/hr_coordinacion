<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\tbl_programacion_contrato;
use App\Models\Movilidad;

class EjecutadasProgramacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ejecutadas-programacion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta y Actualización programadas en Movilidad';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $programadas = tbl_programacion_contrato::where('EJECUTADA', 0)
            ->where('FECHA_AGENDAMIENTO', '>=', date('Y-m-d'))
            ->get();

        $cierres = [
            'CERTIFICADA',
            'CERTIFICADA CON NOVEDADES',
            'INSPECCIONADA CON DEFECTO CRITICO VALLE',
            'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
        ];

        foreach ($programadas as $programada) {

            $tipos_trabajo_rp = array("10444", "12161");
            $tipos_trabajo_sa = array("12163", "12164");

            if (in_array($programada->TIPO_TRABAJO, $tipos_trabajo_rp)) {
                $tipo_trabajo = ["RP 10444", "RP 12161"];
            } elseif (in_array($programada->TIPO_TRABAJO, $tipos_trabajo_sa)) {
                $tipo_trabajo = ["SA " . $programada->TIPO_TRABAJO];
            } elseif ($programada->TIPO_TRABAJO == "12162") {
                $tipo_trabajo = ["RN " . $programada->TIPO_TRABAJO];
            }

            $contrato = ":" . $programada->CONTRATO;

            $movilidad = Movilidad::where('NroSitio', $contrato)
                ->whereIn('TipoTarea', $tipo_trabajo)
                ->whereIn('Cierre1', $cierres)
                ->where('Grupo', 'INSP-VALLE')
                ->exists();

            if($movilidad){
                $programada->EJECUTADA = 1;
                $programada->save();
            }
        }

        return 'OK';
    }
}
