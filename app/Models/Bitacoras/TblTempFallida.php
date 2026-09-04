<?php

namespace App\Models\Bitacoras;

use Illuminate\Database\Eloquent\Model;

class TblTempFallida extends Model
{

    protected $fillable = [
        'NOMBRE',
        'CC_OPERARIO',
        'MUNICIPIO',
        'FECHA',
        'No_ACTA' ,
        'TIPO_TRABAJO',
        'CONTRATO',
        'ORDEN_TRABAJO',
        'ORDEN_EXT',
        'CATEGORIA',
        'RESULTADO_CIERRE',
        'id_bitacora',
        'id_usuario',
        'id_super',
    ];
}
