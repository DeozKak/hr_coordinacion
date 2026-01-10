<?php

namespace App\Models\Zonificacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\relations\HasManyThrough;
use Illuminate\Database\Eloquent\relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblBarrios extends Model implements AuditableContract
{
    use HasFactory, AuditableTrait;

    protected $fillable = ['barrio'];

    public function municipios(){
        return $this->hasManyThrough(
            tbl_localidades_municipio::class,
            TblGruposDetalle::class,
            'id_barrio',
            'id',
            'id',
            'id_mun'
        );
    }

    public function detalle(){
        return $this->HasOne(
            TblGruposDetalle::class,
            'id_barrio',
            'id'
        );
    }

}
