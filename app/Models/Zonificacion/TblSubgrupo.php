<?php

namespace App\Models\Zonificacion;

use App\Models\tbl_insp_cali;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Zonificacion\tbl_localidades_sede;
use Illuminate\Database\Eloquent\relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblSubgrupo extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;

    protected $table = 'tbl_subgrupos';
    public function sede(): HasOne
    {
        return $this->hasOne(tbl_localidades_sede::class,'id','id_sede');
    }



}
