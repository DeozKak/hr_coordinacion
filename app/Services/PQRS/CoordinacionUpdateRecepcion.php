<?php

namespace App\Services\PQRS;

use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\TblInspCali;
use App\Models\TblQuejasContrato;

class CoordinacionUpdateRecepcion
{


    public static function Responsables($completeData)
    {
        $todos_inspectores = TblInspCali::all();
        $tipos_trabajo_rp = array("10444", "12161");
        $tipos_trabajo_sa = array("12163", "12164");

        $contratosConPrefijo = $completeData->pluck('CONTRATO')
            ->filter()
            ->unique()
            ->map(fn($item) => ':' . $item);

        // 2. Realizamos la consulta ordenando por fecha descendente
        $bitacoras = TblBitacoraContrato::whereIn('CONTRATO', $contratosConPrefijo)
            ->select('CONTRATO', 'TIPO_TRABAJO', 'CC_OPERARIO', 'FECHA')
            ->orderBy('FECHA', 'desc') // Los más recientes primero
            ->get()
            // 3. Filtramos la colección para dejar solo un registro por contrato
            ->unique('CONTRATO')
            // 4. Agrupamos por contrato para mantener tu estructura original
            ->groupBy('CONTRATO');

        // Mapeamos responsable en los datos
        foreach ($completeData as $queja) {
            // Si el responsable ya está guardado en BD, lo dejamos así
            // Si está vacío, intentamos buscarlo y actualizar la BD
            if (empty($queja->RESPONSABLE) && $queja->CONTRATO && $queja->TIPO_TRABAJO_CIERRE_ULTIMA) {
                if (in_array($queja->TIPO_TRABAJO_CIERRE_ULTIMA, $tipos_trabajo_rp)) {
                    $tipo_trabajo = "RP ".$queja->TIPO_TRABAJO_CIERRE_ULTIMA;
                } elseif (in_array($queja->TIPO_TRABAJO_CIERRE_ULTIMA, $tipos_trabajo_sa)) {
                    $tipo_trabajo = "SA " . $queja->TIPO_TRABAJO_CIERRE_ULTIMA;
                } elseif ($queja->TIPO_TRABAJO_CIERRE_ULTIMA == "12162") {
                    $tipo_trabajo = "RN " . $queja->TIPO_TRABAJO_CIERRE_ULTIMA;
                }
                //dd($tipo_trabajo);
                $quejaBitacoras = $bitacoras->get(":".$queja->CONTRATO);

                if ($quejaBitacoras) {
                    $bitacora = $quejaBitacoras->firstWhere('TIPO_TRABAJO', $tipo_trabajo);
                    if ($bitacora && $bitacora->CC_OPERARIO) {
                        $inspector = $todos_inspectores->firstWhere('cedula', $bitacora->CC_OPERARIO);
                        if ($inspector) {
                            $responsableFormat = "{$inspector->id}. {$inspector->apellidos} {$inspector->nombres}";
                            $queja->RESPONSABLE = $responsableFormat;
                            // Guardamos el responsable encontrado para futuras consultas
                            $queja->save();
                        }
                    }
                }
            }
        }
    }

    public static function verificarYActualizarRecepcion($completeData)
    {
        // Traemos las quejas cruzadas para optimizar
        $ordenes = $completeData->pluck('NUMERO_ORDEN')->filter()->unique();
        $quejasContrato = TblQuejasContrato::whereIn('ORDEN_TRABAJO', $ordenes)
            ->get(['CONTRATO', 'ORDEN_TRABAJO', 'RESULTADO_CIERRE'])
            ->groupBy('ORDEN_TRABAJO');
        //dd($quejasContrato);
        foreach ($completeData as $queja) {
            // Solo actualizamos automáticamente si el campo RECEPCION está vacío
            if (empty($queja->RECEPCION) && $queja->NUMERO_ORDEN) {
                // Buscamos si existe en tbl_quejas_contrato
                $cruces = $quejasContrato->get($queja->NUMERO_ORDEN);

                if ($cruces) {
                    // Validamos contrato y estado
                    // NOTA: en tbl_quejas_contrato puede que el contrato no tenga prefijo o tenga ":". Ajustamos si es necesario.
                    $match = $cruces->first(function($item) use ($queja) {
                        return (str_replace(':', '', $item->CONTRATO) == str_replace(':', '', $queja->CONTRATO))
                            && (trim(strtoupper($item->RESULTADO_CIERRE)) === 'EJECUTADA');
                    });

                    if ($match) {
                        $queja->RECEPCION = 'GDW';
                        $queja->FECHA_RECEPCION = date('Y-m-d'); // <-- Se asigna la fecha automáticamente
                        $queja->save();
                    }
                }
            }
        }
    }
}
