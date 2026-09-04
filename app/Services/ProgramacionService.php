<?php

namespace App\Services;

use App\Models\Bitacoras\TblBitacoraContrato;
use Illuminate\Support\Facades\DB;

class ProgramacionService
{
    /**
     * Cierres que cuentan como trabajo ejecutado, por origen.
     *
     * Van separados por tabla porque cada una escribe el mismo estado a su
     * manera: la bitácora se carga desde el Excel y llega limpia, mientras que
     * reportes_diarios viene tal cual de movilidad, que antepone un punto a
     * algunos estados.
     */
    private const CERTIFICADOS = [
        'bitacora' => ['CERTIFICADA', 'CERTIFICADA CON NOVEDADES'],
        'reporte'  => ['.CERTIFICADA', 'CERTIFICADA CON NOVEDADES'],
    ];

    private const CON_DEFECTO = [
        'bitacora' => [
            'INSPECCIONADA CON DEFECTO CRITICO VALLE',
            'INSPECCIONADA CON DEFECTO NO CRITICO VALLE',
        ],
        'reporte' => [
            '.INSPECCIONADA CON DEFECTO CRITICO VALLE',
            '.INSPECCIONADA CON DEFECTO NO CRITICO VALLE',
        ],
    ];

    private array $tipos_trabajo_rp = ['10444', '12161'];
    private array $tipos_trabajo_sa = ['12163', '12164'];

    /**
     * Busca si un contrato ya se ejecutó.
     *
     * Son dos fuentes. La bitácora de contratos es el registro histórico y se
     * consulta por contrato, orden y tipo. reportes_diarios es lo que movilidad
     * deja al día; como esa tabla no guarda la orden de trabajo se busca solo
     * por contrato y tipo, y a cambio ve lo cerrado hoy, antes de que la
     * bitácora se suba.
     *
     * Devuelve siempre la misma forma —CC_OPERARIO, FECHA, RESULTADO_CIERRE y
     * TIPO_TRABAJO— venga de donde venga, porque quien llama lee esos campos.
     */
    public function findExecuted($contrato, $tipo_trabajo, $orden)
    {
        $tipos = $this->tiposEquivalentes($tipo_trabajo);

        // Un trabajo que no reconocemos no se da por ejecutado: se deja programar.
        if ($tipos === []) {
            return null;
        }

        $contrato = ':' . $contrato;

        return $this->enBitacora($contrato, $tipos, $orden)
            ?? $this->enReportesDiarios($contrato, $tipos);
    }

    /**
     * Los códigos con los que pudo quedar cerrado un tipo de trabajo.
     *
     * Revisión periódica se cierra indistintamente como 10444 o 12161, así que
     * cualquiera de los dos vale. Fuera de esos cinco códigos no hay
     * equivalencia que aplicar y se devuelve vacío: un trabajo raro llega
     * siempre acompañado de uno normal, y si por una vez viniera solo es
     * preferible dejarlo programar que bloquearlo por una coincidencia de
     * nombre.
     */
    private function tiposEquivalentes($tipo_trabajo): array
    {
        if (in_array($tipo_trabajo, $this->tipos_trabajo_rp)) {
            return ['RP 10444', 'RP 12161'];
        }

        if (in_array($tipo_trabajo, $this->tipos_trabajo_sa)) {
            return ['SA ' . $tipo_trabajo];
        }

        if ($tipo_trabajo == '12162') {
            return ['RN 12162'];
        }

        return [];
    }

    /**
     * Los cierres que impiden volver a programar ese tipo de trabajo.
     *
     * SA 12164 es la excepción: cerrado con defecto hay que volver a
     * inspeccionarlo, así que solo bloquea cuando quedó certificado. Para el
     * resto de trabajos cualquiera de los cuatro cierres es punto final.
     */
    private function cierresQueBloquean(array $tipos, string $origen): array
    {
        if ($tipos === ['SA 12164']) {
            return self::CERTIFICADOS[$origen];
        }

        return array_merge(self::CERTIFICADOS[$origen], self::CON_DEFECTO[$origen]);
    }

    private function enBitacora(string $contrato, array $tipos, $orden)
    {
        return TblBitacoraContrato::select('CC_OPERARIO', 'FECHA', 'RESULTADO_CIERRE', 'TIPO_TRABAJO')
            ->where('CONTRATO', $contrato)
            ->where('ORDEN_TRABAJO', $orden)
            ->whereIn('TIPO_TRABAJO', $tipos)
            ->whereIn('RESULTADO_CIERRE', $this->cierresQueBloquean($tipos, 'bitacora'))
            ->first();
    }

    /**
     * Lo que movilidad reporta cada día, sin orden de trabajo de por medio.
     *
     * Se renombran las columnas a los nombres de la bitácora para que el
     * resultado sea intercambiable, y se toma el cierre más reciente: un mismo
     * contrato puede tener varios intentos y el que vale es el último.
     */
    private function enReportesDiarios(string $contrato, array $tipos)
    {
        return DB::table('reportes_diarios')
            ->select(
                'NroOperario as CC_OPERARIO',
                'FechaRealFin as FECHA',
                'Cierre3 as RESULTADO_CIERRE',
                'TipoTarea as TIPO_TRABAJO'
            )
            ->where('NroSitio', $contrato)
            ->whereIn('TipoTarea', $tipos)
            ->whereIn('Cierre3', $this->cierresQueBloquean($tipos, 'reporte'))
            ->orderByDesc('FechaRealFin')
            ->first();
    }
}
