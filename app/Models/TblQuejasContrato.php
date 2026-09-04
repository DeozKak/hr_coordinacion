<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblQuejasContrato extends Model
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
    ];
}
