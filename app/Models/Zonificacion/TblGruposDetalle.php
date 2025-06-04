<?php

namespace App\Models\Zonificacion;

use App\Models\tbl_insp_cali;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\relations\BelongsTo;
use App\Models\Zonificacion\TblGrupo;
use App\Models\Zonificacion\TblSubgrupo;
use App\Models\Zonificacion\TblBarrios;
use App\Models\Zonificacion\tbl_localidades_municipio;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class TblGruposDetalle extends Model implements AuditableContract
{
    use HasFactory, AuditableTrait;

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


    public function tbl_grupo()
    {
        return $this->belongsTo(TblGrupo::class, 'id_grupo');
    }

    public function tbl_subgrupo()
    {
        return $this->belongsTo(TblSubgrupo::class, 'id_subGrupo');
    }

    public function tbl_barrios()
    {
        return $this->belongsTo(TblBarrios::class, 'id_barrio');
    }

    public function tbl_localidades_municipio()
    {
        return $this->belongsTo(tbl_localidades_municipio::class, 'id_mun');
    }

    public function inspectores(): BelongsToMany
    {
        return $this->belongsToMany(
            tbl_insp_cali::class,
            'tbl_inspector_detalle',
            'detalle_id',
            'inspector_id'
        );
    }

}
