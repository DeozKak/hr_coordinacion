<?php

namespace App\Models\Bitacoras;

use Illuminate\Database\Eloquent\Model;

class TblBitacoraFallida extends Model
{
    protected $fillable = [
        'NOMBRE',
        'id',
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
        'created_at',
        'updated_at',
        'id_bitacora',
        'id_usuario',
        'id_super'
    ];
}
