<?php

namespace App\Console\Commands;


use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use App\Models\tbl_insp_cali;
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
        $programadas = tbl_programacion_contrato::where('EJECUTADA', 0)
            ->where('FECHA_AGENDAMIENTO', '>=', date('Y-m-d'))
            ->get();

        $cierres = [
            'CERTIFICADA',
            'CERTIFICADA CON NOVEDADES',
            'INSPECCIONADA CON DEFECTO CRITICO VALLE',
            'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
        ];
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
           // $dosAnosAtras = Carbon::now()->subYears(2)->toDateString();
            $bitacora = tbl_bitacora_contrato::select('CC_OPERARIO', 'FECHA', 'RESULTADO_CIERRE', 'TIPO_TRABAJO')
                ->where('CONTRATO', $contrato)
                ->whereIn('TIPO_TRABAJO', $tipo_trabajo)
                ->whereIn('RESULTADO_CIERRE', $cierres)
                ->first();

            if($bitacora){
                 if(in_array($bitacora->RESULTADO_CIERRE, ['INSPECCIONADA CON DEFECTO CRITICO VALLE','INSPECCIONADA CON DEFECTO NO CRITICO VALLE']) && $bitacora->TipoTarea === 'SA 12164'){

                }else{
                   /* $fecha_completa = $movilidad->FechaRealInicio;
                    $partes = explode(' ', $fecha_completa);
                    $fecha = $partes[0];
                    if($fecha <= $dosAnosAtras){

                    }else{*/
                        $programada->EJECUTADA = 1;
                        $programada->save();
                    /*}*/
                }
            }else{
                $consulta = DB::table('tbl_bitacora_diaria')->select('CC_OPERARIO', 'FECHA', 'RESULTADO_CIERRE', 'TIPO_TRABAJO')
                    ->where('CONTRATO', $contrato)
                    ->whereIn('TIPO_TRABAJO', $tipo_trabajo)
                    ->whereIn('RESULTADO_CIERRE', $cierres)
                    ->first();
                if($consulta) {
                    if (in_array($consulta->RESULTADO_CIERRE, ['INSPECCIONADA CON DEFECTO CRITICO VALLE', 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE', 'INSPECCIONADA CON DEFECTO CRITICO VALLE']) && $consulta->TIPO_TRABAJO === 'SA 12164') {

                    } else {
                        $programada->EJECUTADA = 1;
                        $programada->save();
                    }
                }
            }
        }

        return 'OK';
    }
}
