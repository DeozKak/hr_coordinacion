<?php

namespace App\Models\Stickers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class tbl_sticker_tipo extends Model
{
    protected $table = "tbl_sticker_tipos";

    public function Inventario(): HasOne
    {
        return $this->hasOne(tbl_sticker_inventario::class, 'id_sticker_tipo', 'id');
    }
}
