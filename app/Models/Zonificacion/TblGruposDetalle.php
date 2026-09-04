<?php

namespace App\Models\Zonificacion;

use App\Models\TblInspCali;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TblGruposDetalle extends Model implements AuditableContract
{
    use AuditableTrait, HasFactory;

    /**
     * @var int|mixed
     */
    protected $fillable = [
        'id_mun',
        'id_grupo',
        'id_subGrupo',
        'id_barrio',
    ];

    protected $table = 'tbl_grupos_detalle';

    public function grupo()
    {
        return $this->belongsTo(TblGrupo::class, 'id_grupo');
    }

    public function subgrupo()
    {
        return $this->belongsTo(TblSubgrupo::class, 'id_subGrupo');
    }

    public function barrio()
    {
        return $this->belongsTo(TblBarrios::class, 'id_barrio');
    }

    public function municipio()
    {
        return $this->belongsTo(TblLocalidadesMunicipio::class, 'id_mun');
    }

    public function inspectores(): BelongsToMany
    {
        return $this->belongsToMany(
            TblInspCali::class,
            'tbl_inspector_detalle',
            'detalle_id',
            'inspector_id'
        );
    }
}
