<?php

namespace App\Services;
use App\Models\Zonificacion\TblGruposDetalle;
use PhpParser\Node\Expr\Cast\Object_;
use Psy\Util\Json;

class MunicipioService
{

    /**
     * verifica si hay municipios sin un grupo o sub grupo asignado
     *
     * @return boolean
     * */
    public function VerificarGrupo():bool
    {
        return  TblGruposDetalle::whereNull('id_grupo')->orWhereNull('id_subGrupo')->exists();
    }

    public function MunicipiosSinGrupo():Object
    {
        return TblGruposDetalle::whereNull('id_grupo')->orWhereNull('id_subGrupo')->get();
    }
}
