<?php

namespace App\Models\Zonificacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Zonificacion\tbl_localidades_sede;
use Illuminate\Database\Eloquent\relations\HasOne;

class TblSubgrupo extends Model
{
    use HasFactory;

    protected $table = 'tbl_subgrupos';
    public function tbl_localidades_sede(){
        return $this->hasOne(tbl_localidades_sede::class,'id_sede','id');
    }
}
