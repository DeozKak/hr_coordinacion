<?php

namespace App\Models\Zonificacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\relations\HasOne;
use App\Models\Zonificacion\tbl_localidades_sede;
class TblGrupo extends Model
{
    use HasFactory;

    protected $table = 'tbl_grupos';
    public function tbl_localidades_sede(){
        return $this->hasOne(tbl_localidades_sede::class,'id_sede','id');
    }

}
