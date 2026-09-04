<?php

namespace App\Models\Zonificacion;

use App\Models\TblInspCali;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Zonificacion\TblLocalidadesSede;
use Illuminate\Database\Eloquent\relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblSubgrupo extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;

    protected $table = 'tbl_subgrupos';

    protected $fillable = ['subgrupo'];
    public function municipios()
    {
        return $this->belongsToMany(
            TblLocalidadesMunicipio::class,
            'tbl_grupos_detalle', // Nombre de la tabla intermedia (pivote)
            'id_subGrupo',           // Clave foránea en la tabla pivote para este modelo
            'id_mun'              // Clave foránea en la tabla pivote para el modelo relacionado
        );
    }

    /**
     * Accesor para obtener la Sede.
     * Al llamar a $grupo->sede, Laravel buscará el primer municipio y traerá su sede.
     */
    public function getSedeAttribute()
    {
        // Verifica si tiene municipios asignados y devuelve la sede del primero
        return $this->municipios->first()?->sede;
    }



}
