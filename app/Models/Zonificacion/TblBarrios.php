<?php

namespace App\Models\Zonificacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\relations\HasManyThrough;

class TblBarrios extends Model
{
    use HasFactory;

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

}
