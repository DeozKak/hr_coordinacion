<?php

namespace App\Services;
use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use Illuminate\Support\Facades\DB;

class ProgramacionService
{

    private array $cierres = [
        'CERTIFICADA',
        'CERTIFICADA CON NOVEDADES',
        'INSPECCIONADA CON DEFECTO CRITICO VALLE',
        'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
    ];
   private array $tipos_trabajo_rp = array("10444", "12161");
   private array $tipos_trabajo_sa = array("12163", "12164");

    public  function findExecuted($contrato,$tipo_trabajo,$orden){

        if (in_array($tipo_trabajo, $this->tipos_trabajo_rp)) {
            $tipo_trabajo = ["RP 10444", "RP 12161"];
        } elseif (in_array($tipo_trabajo, $this->tipos_trabajo_sa)) {
            $tipo_trabajo = ["SA " . $tipo_trabajo];
        } elseif ($tipo_trabajo == "12162") {
            $tipo_trabajo = ["RN " . $tipo_trabajo];
        }

        $contrato = ':' . $contrato;
        if($tipo_trabajo === 'RP 12161'){
            $bitacora = tbl_bitacora_contrato::select('CC_OPERARIO', 'FECHA', 'RESULTADO_CIERRE', 'TIPO_TRABAJO')
            ->where('CONTRATO', $contrato)
            ->where('ORDEN_EXT',$orden)
            ->whereIn('TIPO_TRABAJO', $tipo_trabajo)
            ->whereIn('RESULTADO_CIERRE', $this->cierres)
            ->first();
        }
        $bitacora = tbl_bitacora_contrato::select('CC_OPERARIO', 'FECHA', 'RESULTADO_CIERRE', 'TIPO_TRABAJO')
            ->where('CONTRATO', $contrato)
            ->where('ORDEN_TRABAJO',$orden)
            ->whereIn('TIPO_TRABAJO', $tipo_trabajo)
            ->whereIn('RESULTADO_CIERRE', $this->cierres)
            ->first();

        if($bitacora){

            return $bitacora;

        }else{
             if($tipo_trabajo === 'RP 12161'){
            $consulta = tbl_bitacora_contrato::select('CC_OPERARIO', 'FECHA', 'RESULTADO_CIERRE', 'TIPO_TRABAJO')
            ->where('CONTRATO', $contrato)
            ->where('ORDEN_EXT',$orden)
            ->whereIn('TIPO_TRABAJO', $tipo_trabajo)
            ->whereIn('RESULTADO_CIERRE', $this->cierres)
            ->first();
        }
            $consulta = DB::table('tbl_bitacora_diaria')->select('CC_OPERARIO', 'FECHA', 'RESULTADO_CIERRE', 'TIPO_TRABAJO')
                ->where('CONTRATO', $contrato)
                ->where('ORDEN_TRABAJO',$orden)
                ->whereIn('TIPO_TRABAJO', $tipo_trabajo)
                ->whereIn('RESULTADO_CIERRE', $this->cierres)
                ->first();

            return $consulta;
        }
    }


}
