<?php

namespace App\Models;

use App\Models\Stickers\tbl_asignacion_sticker_historial;
use App\Models\Stickers\tbl_inspector_sticker;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use App\Models\Zonificacion\AsignacionTecnicoLocalidad;
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

    /**
     * Dar de baja a un inspector lo saca de la fuerza de trabajo.
     *
     * Un inspector desactivado no puede seguir asignado a una localidad, así
     * que se le retira en el momento en que se le apaga el estado. Va en el
     * modelo y no en el controlador para que valga por cualquier camino que
     * cambie el estado, no sólo por el botón de gestión de inspectores.
     *
     * Sólo alcanza a lo que pase por Eloquent: un UPDATE directo contra la
     * base no dispara nada. Por eso la tarjeta filtra además por estado al
     * leer, y así una fila huérfana tampoco se vería.
     */
    protected static function booted(): void
    {
        static::saved(function (self $inspector) {
            if ($inspector->wasChanged('state') && (int) $inspector->state !== 1) {
                AsignacionTecnicoLocalidad::where('id_tecnico', $inspector->id)->delete();
            }
        });

        static::deleted(function (self $inspector) {
            AsignacionTecnicoLocalidad::where('id_tecnico', $inspector->id)->delete();
        });
    }

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

    public function contratos()
    {
        return $this->hasMany(tbl_bitacora_contrato::class, 'CC_OPERARIO', 'cedula');
    }


}
