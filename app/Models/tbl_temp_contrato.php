<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tbl_temp_contrato extends Model
{
    use HasFactory;

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
        'HORA_INICIO',
        'HORA_FINAL',
        'VENCE',
        'id_bitacora',
        'id_usuario',
        'id_super'
    ];
}
