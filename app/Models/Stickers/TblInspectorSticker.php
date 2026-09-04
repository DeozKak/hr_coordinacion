<?php

namespace App\Models\Stickers;

use Illuminate\Database\Eloquent\Model;

class TblInspectorSticker extends Model
{
    protected $fillable = [
        'id_inspector',
        'id_sticker_tipo',
        'cantidad_asignada'
    ];
}
