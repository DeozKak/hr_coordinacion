<?php

namespace App\Models;

use App\Models\Stickers\tbl_asignacion_sticker_historial;
use App\Models\Stickers\tbl_inspector_sticker;
use App\Models\Zonificacion\TblSubgrupo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class tbl_insp_cali extends Model implements AuditableContract
{
    use HasFactory;
    use AuditableTrait;
    protected $table = 'tbl_insp_cali';

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class,'SUPERVISOR');
    }

    public function detalles(): BelongsToMany
    {
        return $this->belongsToMany(
            TblSubgrupo::class,
            'tbl_inspector_detalle',
            'inspector_id',
            'detalle_id'
        );
    }

    public function Stickers(): HasMany
    {
        return $this->hasMany(tbl_inspector_sticker::class,'id_inspector','id');
    }

    public function HistoricoStickers(): HasMany
    {
        return $this->hasMany(tbl_asignacion_sticker_historial::class,'id_inspector','id');
    }

}
