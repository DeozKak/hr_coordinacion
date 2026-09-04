<?php

namespace App\Models\Zonificacion;

use App\Models\Produccion\TblProduccionZona;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblLocalidadesMunicipio extends Model implements AuditableContract
{
    use HasFactory, AuditableTrait;

    public $timestamps = false;
    protected $fillable = ['nombre'];
    public function barrios(): HasManyThrough
    {
        return $this->HasManyThrough(
            TblBarrios::class,
            TblGruposDetalle::class,
            'id_mun',
            'id',
            'id',
            'id_barrio'
        );
    }
    public function grupos()
    {
        return $this->hasManyThrough(
            TblGrupo::class,           // Modelo final (el que quieres obtener)
            TblGruposDetalle::class,    // Modelo intermedio (la tabla pivote)
            'id_mun', // Clave foránea en TblGruposDetalle que relaciona con TblLocalidadesMunicipio
            'id',                      // Clave foránea en TblGrupos (tbl_grupos.tbl_grupos_detalle_id)
            'id',                      // Clave local en TblLocalidadesMunicipio
            'id_grupo'   // Clave local en TblGruposDetalle que relaciona con TblGrupos
        );
    }



public function subgrupos(): HasManyThrough
    {
        return $this->HasManyThrough(
            TblSubgrupo::class,
            TblGruposDetalle::class,
            'id_mun',
            'id',
            'id',
            'id_subgrupo'
        );
    }
    public function sede(): BelongsTo
    {
        return $this->belongsTo(TblLocalidadesSede::class, 'id_sede', 'id');
    }
    public function zona(): BelongsTo
    {
        return $this->belongsTo(TblProduccionZona::class, 'id_zona', 'id');
    }
}
