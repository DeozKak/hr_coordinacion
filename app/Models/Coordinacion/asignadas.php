<?php

namespace App\Models\Coordinacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class asignadas extends Model
{
    use HasFactory;

    protected $fillable = [
        'orden_trabajo_cerrada',
        'contrato_cerrada',
        'producto_cerrada',
        'tipo_trabajo_cerrada',
        'fecha_legalizacion',
        'comentario_legalizacion',
        'cod_causal',
        'des_causal',
        'consecutivo',
        'dias_proceso',
        'status',
        'causa_cierre',
        'fecha_solicitud_cierre',
        'marca',
    ];
}
