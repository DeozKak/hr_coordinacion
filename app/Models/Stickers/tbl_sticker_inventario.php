<?php

namespace App\Models\Stickers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
class tbl_sticker_inventario extends Model
{

    protected $table = "tbl_sticker_inventario";

    public function Inventario(): HasOne
    {
        return $this->hasOne(tbl_sticker_tipo::class);
    }
}
