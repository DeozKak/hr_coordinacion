<?php

namespace App\Models\Stickers;

use App\Models\TblInspCali;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblStickerActaSerial extends Model
{
    use HasFactory;

    protected $table = 'tbl_sticker_acta_seriales';

    protected $fillable = [
        'id_sticker_tipo',
        'serial',
        'estado',
        'id_inspector',
    ];

    /**
     * Relación con el tipo de sticker (aunque siempre será "ACTA")
     */
    public function tipo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TblStickerTipo::class, 'id_sticker_tipo');
    }

    /**
     * Relación con el inspector asignado
     */
    public function inspector(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TblInspCali::class, 'id_inspector');
    }
}
