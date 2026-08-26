<?php

namespace App\Console\Commands;


use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use App\Models\Movilidad;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Console\Command;


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

        $inicioDia = Carbon::now()->startOfDay()->toDateTimeString();
        $finDia = Carbon::now()->endOfDay()->toDateTimeString();

        $estadosCierre = [
            '.CERTIFICADA',
            'CERTIFICADA CON NOVEDADES',
            '.INSPECCIONADA CON DEFECTO CRITICO VALLE',
            '.INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
        ];

        $programadas = tbl_programacion_contrato::where('EJECUTADA', 0)
            ->where('FECHA_AGENDAMIENTO', '>=', date('Y-m-d'))
            ->get();


        $tipos_trabajo_rp = array("10444", "12161");
        $tipos_trabajo_sa = array("12163", "12164");
        foreach ($programadas as $programada) {

            if (in_array($programada->TIPO_TRABAJO, $tipos_trabajo_rp)) {
                $tipo_trabajo = ["RP 10444", "RP 12161"];
            } elseif (in_array($programada->TIPO_TRABAJO, $tipos_trabajo_sa)) {
                $tipo_trabajo = ["SA " . $programada->TIPO_TRABAJO];
            } elseif ($programada->TIPO_TRABAJO == "12162") {
                $tipo_trabajo = ["RN " . $programada->TIPO_TRABAJO];
            }

            $contrato = ":" . $programada->CONTRATO;
            $dosAnosAtras = Carbon::now()->subYears(2)->toDateString();

            $bitacora = DB::table('reportes_diarios')->select('NroOperario', 'FechaRealFin', 'Cierre3', 'TipoTarea')
                ->where('NroSitio', $contrato)
                ->whereIn('TipoTarea', $tipo_trabajo)
                ->whereIn('Cierre3', $estadosCierre)
                ->first();

            if($bitacora){
                 if(in_array($bitacora->Cierre3, ['.INSPECCIONADA CON DEFECTO CRITICO VALLE','.INSPECCIONADA CON DEFECTO NO CRITICO VALLE']) && $bitacora->TipoTarea === 'SA 12164'){

                }else{
                    $fecha_completa = $bitacora->FechaRealFin;
                    $partes = explode(' ', $fecha_completa);
                    $fecha = $partes[0];
                    if($fecha <= $dosAnosAtras){

                    }else {
                        $programada->EJECUTADA = 1;
                        $programada->save();
                    }
                }
            }
        }

        return 'OK';
    }
}
