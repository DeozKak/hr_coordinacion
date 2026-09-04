<?php

namespace App\Models\Stickers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TblStickerTipo extends Model
{
    protected $table = "tbl_sticker_tipos";

    public function Inventario(): HasOne
    {
        return $this->hasOne(TblStickerInventario::class, 'id_sticker_tipo', 'id');
    }
}
