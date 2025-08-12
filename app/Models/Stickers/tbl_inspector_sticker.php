<?php

namespace App\Models\Stickers;

use Illuminate\Database\Eloquent\Model;

class tbl_inspector_sticker extends Model
{
    protected $fillable = [
        'id_inspector',
        'id_sticker_tipo',
        'cantidad_asignada'
    ];
}
