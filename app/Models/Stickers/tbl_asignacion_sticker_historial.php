<?php

namespace App\Models\Stickers;

use Illuminate\Database\Eloquent\Model;

class tbl_asignacion_sticker_historial extends Model
{
    protected $table = 'tbl_asignacion_sticker_historial';
    protected $fillable =[
        'id_inspector',
        'id_sticker_tipo',
        'cantidad',
        'fecha_asignacion',
        'id_usuario_asigna'
    ];
}
