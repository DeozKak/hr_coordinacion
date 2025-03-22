<?php

namespace App\Services;
use App\Models\Zonificacion\TblGruposDetalle;
use App\Models\Zonificacion\TblBarrios;

class BarrioService
{

    /**
     * verifica duplicados según los parametros entregados
     * realiza dos consultas una para la tabla de barrios
     * para verificar que el barrio existe, si existe, itera los elementos
     * y para hacer la consulta de relación con el municipio si ya eciste relacion
     * devuelve TRUE si no FALSE
     *
     *
     * @param int $id_mun
     * @param String|null $nom_barrio
     * @param int|null $id_barrio
     * @return bool
     */
    public function duplicado(int $id_mun,String $nom_barrio = null, int $id_barrio = null):bool
    {
        $query = TblBarrios::query(); // Inicia la consulta

        if ($nom_barrio !== null) {
            $query->where('barrio', $nom_barrio);
        } elseif ($id_barrio !== null) {
            $query->where('id', $id_barrio);
        }

        $barrios = $query->get(); // Ejecuta la consulta

        if(count($barrios) > 0){
            foreach($barrios as $barrio){
                $consulta = TblGruposDetalle::where('id_mun',$id_mun)
                    ->where('id_barrio',$barrio->id)->exists();
                if($consulta){
                    return true;
                }
            }
        }
        return false;
    }



}
