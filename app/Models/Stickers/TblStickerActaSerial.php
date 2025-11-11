<?php

namespace App\Models\Stickers;

use App\Models\tbl_insp_cali;
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
        return $this->belongsTo(tbl_sticker_tipo::class, 'id_sticker_tipo');
    }

    /**
     * Relación con el inspector asignado
     */
    public function inspector(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(tbl_insp_cali::class, 'id_inspector');
    }
}
