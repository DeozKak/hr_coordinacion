<?php

namespace App\Services\Programacion;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use Illuminate\Support\Facades\DB;

class ExecutionVerifier
{

    private array $cierres = [
        'CERTIFICADA',
        'CERTIFICADA CON NOVEDADES',
        'INSPECCIONADA CON DEFECTO CRITICO VALLE',
        'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
    ];
   private array $tipos_trabajo_rp = array("10444", "12161");
   private array $tipos_trabajo_sa = array("12163", "12164");

    public function findExecuted($contrato,$tipo_trabajo)
    {

        if (in_array($tipo_trabajo, $this->tipos_trabajo_rp)) {
            $tipo_trabajo = ["RP 10444", "RP 12161"];
        } elseif (in_array($tipo_trabajo, $this->tipos_trabajo_sa)) {
            $tipo_trabajo = ["SA " . $tipo_trabajo];
        } elseif ($tipo_trabajo == "12162") {
            $tipo_trabajo = ["RN " . $tipo_trabajo];
        }

        $contrato = ':' . $contrato;

        $bitacora = tbl_bitacora_contrato::select('CC_OPERARIO', 'FECHA', 'RESULTADO_CIERRE', 'TIPO_TRABAJO')
            ->where('CONTRATO', $contrato)
            ->whereIn('TIPO_TRABAJO', $tipo_trabajo)
            ->whereIn('RESULTADO_CIERRE', $this->cierres)
            ->first();

        if($bitacora){

            return $bitacora;

        }else{
            $consulta = DB::table('tbl_bitacora_diaria')->select('CC_OPERARIO', 'FECHA', 'RESULTADO_CIERRE', 'TIPO_TRABAJO')
                ->where('CONTRATO', $contrato)
                ->whereIn('TIPO_TRABAJO', $tipo_trabajo)
                ->whereIn('RESULTADO_CIERRE', $this->cierres)
                ->first();

            return $consulta;
        }
    }




}
