<?php

namespace App\Models\Stickers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TblStickerInventario extends Model
{
    protected $table = 'tbl_sticker_inventario';

    /* StickersController hace firstOrCreate(['id_sticker_tipo' => $id]): sin
       esto el modelo queda totalmente protegido y la creación —sólo la
       creación, no la búsqueda— lanza MassAssignmentException. El fallo no se
       ve hasta que aparece un tipo de sticker sin fila de inventario. */
    protected $fillable = ['id_sticker_tipo'];

    public function Inventario(): HasOne
    {
        return $this->hasOne(TblStickerTipo::class);
    }
}
