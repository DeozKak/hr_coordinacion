<?php

namespace App\Models\Stickers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
class TblStickerInventario extends Model
{

    protected $table = "tbl_sticker_inventario";

    public function Inventario(): HasOne
    {
        return $this->hasOne(TblStickerTipo::class);
    }
}
