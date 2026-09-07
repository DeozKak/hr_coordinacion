<?php

namespace App\Services\Bitacoras;

use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Bitacoras\TblDvInsp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de lectura de una bitácora ya guardada.
 *
 * Las comparte la pantalla del reporte y el Excel que se descarga, de modo que
 * el archivo enseñe lo mismo que se ve por pantalla en lugar de armarse por su
 * cuenta.
 *
 * Contratos y devoluciones se devuelven con la misma forma —el nombre del
 * inspector resuelto y una marca de cuál es cuál— porque en el Excel van
 * juntos, en la hoja de su inspector.
 */
class LecturaBitacoraService
{
    /**
     * Contratos de la bitácora.
     *
     * `vence` se calcula igual que en el reporte: el valor propio si lo tiene,
     * y si no la marca de periodo de gracia.
     */
    public function contratosDe(int $idBitacora): Collection
    {
        return TblBitacoraContrato::query()
            ->selectRaw("CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS nombre_completo")
            ->addSelect('tbl_bitacora_contratos.*')
            ->selectRaw("CASE
                    WHEN tbl_bitacora_contratos.vence IS NOT NULL THEN tbl_bitacora_contratos.vence
                    WHEN tbl_bitacora_contratos.PERIODO_GRACIA = 1 THEN 'PERIODO DE GRACIA'
                    ELSE NULL
                 END AS vence")
            ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_contratos.CC_OPERARIO')
            ->where('tbl_bitacora_contratos.id_bitacora', $idBitacora)
            ->get();
    }

    /**
     * Devoluciones de la bitácora que siguen pendientes.
     *
     * Se filtran las gestionadas a propósito: el archivo se arma al pedirlo, no
     * al guardar, así que enseña lo que queda por resolver hoy y no lo que
     * quedaba el día en que se cerró la bitácora.
     */
    public function devolucionesSinGestionar(int $idBitacora): Collection
    {
        return TblDvInsp::query()
            ->selectRaw("CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS nombre_completo")
            ->addSelect('tbl_dv_insp.*')
            /* `tbl_dv_insp.CC_OPERARIO` quedó con collation utf8mb4_general_ci
               mientras el resto del esquema usa utf8mb4_unicode_ci, así que el
               join directo falla con «Illegal mix of collations». Se iguala
               aquí; arreglarlo de raíz es un ALTER sobre una tabla que usan
               varias pantallas. */
            ->join('tbl_insp_cali', function ($union) {
                $union->on(
                    DB::raw('tbl_insp_cali.cedula COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('tbl_dv_insp.CC_OPERARIO COLLATE utf8mb4_unicode_ci')
                );
            })
            ->where('tbl_dv_insp.id_bitacora', $idBitacora)
            ->where('tbl_dv_insp.GESTIONADO', 0)
            ->get();
    }

    /**
     * Las filas de cada inspector, contratos y devoluciones en la misma lista.
     *
     * Se normalizan a un mismo juego de claves porque acaban en la misma hoja;
     * `es_devolucion` es lo que después pinta el contrato en rojo y lo deja
     * fuera del conteo por tipo de cierre.
     *
     * @return Collection<string, Collection<int, array>>
     */
    public function filasPorInspector(int $idBitacora): Collection
    {
        $contratos = $this->contratosDe($idBitacora)->map(fn ($c) => [
            'nombre_completo' => $c->nombre_completo,
            'CC_OPERARIO' => $c->CC_OPERARIO,
            'MUNICIPIO' => $c->MUNICIPIO,
            'FECHA' => $c->FECHA,
            'No_ACTA' => $c->No_ACTA,
            'TIPO_TRABAJO' => $c->TIPO_TRABAJO,
            'CONTRATO' => $c->CONTRATO,
            'ORDEN_TRABAJO' => $c->ORDEN_TRABAJO,
            'ORDEN_EXT' => $c->ORDEN_EXT,
            'CATEGORIA' => $c->CATEGORIA,
            'RESULTADO_CIERRE' => $c->RESULTADO_CIERRE,
            'HORA_INICIO' => $c->HORA_INICIO,
            'HORA_FINAL' => $c->HORA_FINAL,
            'DURACION_INSP' => $c->DURACION_INSP,
            '4_RECINTOS' => $c->getAttribute('4_RECINTOS'),
            'VENCE' => $c->vence,
            'CAUSAL' => $c->CAUSAL_RECHAZO,
            'es_devolucion' => false,
        ]);

        $devoluciones = $this->devolucionesSinGestionar($idBitacora)->map(fn ($d) => [
            'nombre_completo' => $d->nombre_completo,
            'CC_OPERARIO' => $d->CC_OPERARIO,
            'MUNICIPIO' => $d->MUNICIPIO,
            'FECHA' => $d->FECHA_INSP,
            'No_ACTA' => $d->No_ACTA,
            'TIPO_TRABAJO' => $d->TIPO_TRABAJO,
            'CONTRATO' => $d->CONTRATO,
            'ORDEN_TRABAJO' => $d->ORDEN_TRABAJO,
            'ORDEN_EXT' => $d->ORDEN_EXT,
            'CATEGORIA' => $d->CATEGORIA,
            'RESULTADO_CIERRE' => $d->RESULTADO_CIERRE,
            'HORA_INICIO' => $d->HORA_INICIO,
            'HORA_FINAL' => $d->HORA_FINAL,
            'DURACION_INSP' => $d->DURACION_INSP,
            '4_RECINTOS' => $d->getAttribute('4_RECINTOS'),
            'VENCE' => $d->vence,
            'CAUSAL' => $d->CAUSAL,
            'es_devolucion' => true,
        ]);

        return $contratos->concat($devoluciones)
            ->sortBy([['nombre_completo', 'asc'], ['FECHA', 'asc']])
            ->groupBy('nombre_completo');
    }
}
