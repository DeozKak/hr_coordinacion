<?php

namespace App\Services;
use App\Models\Zonificacion\TblGruposDetalle;

class MunicipioService
{

    /**
     * verifica si hay municipios sin un grupo o sub grupo asignado
     *
     * @return Array
     * */
    public function VerificarGrupo():array
    {

        $detalle = TblGruposDetalle::whereNull('id_grupo')
            ->orWhereNull('id_subGrupo')->get();

        return  $detalle->toArray();

    }

}
