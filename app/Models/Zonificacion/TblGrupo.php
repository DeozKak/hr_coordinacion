<?php

namespace App\Models\Zonificacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\relations\HasOne;
use App\Models\Zonificacion\tbl_localidades_sede;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblGrupo extends Model implements AuditableContract
{
    use HasFactory, AuditableTrait;

    protected $table = 'tbl_grupos';
    public function sede(){
        return $this->hasOne(tbl_localidades_sede::class,'id','id_sede');
    }

}
