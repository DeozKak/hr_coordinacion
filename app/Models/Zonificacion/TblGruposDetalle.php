<?php

namespace App\Models\Zonificacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\relations\BelongsTo;
use App\Models\Zonificacion\TblGrupo;
use App\Models\Zonificacion\TblSubgrupo;
use App\Models\Zonificacion\TblBarrios;
use App\Models\Zonificacion\tbl_localidades_municipio;



class TblGruposDetalle extends Model
{
    use HasFactory;

    protected $table = 'tbl_grupos_detalle';


    public function tbl_grupo(){
        return $this->belongsTo(TblGrupo::class,'id_grupo');
    }

    public function tbl_subgrupo(){
        return $this->belongsTo(TblSubgrupo::class,'id_subgrupo');
    }
    public function tbl_barrios(){
        return $this->belongsTo(TblBarrios::class,'id_barrio');
    }
    public function tbl_localidades_municipio(){
        return $this->belongsTo(tbl_localidades_municipio::class,'id_mun');
    }

}
