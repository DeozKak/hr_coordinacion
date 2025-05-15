<?php

namespace App\Models\Zonificacion;

use App\Models\tbl_insp_cali;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Zonificacion\tbl_localidades_sede;
use Illuminate\Database\Eloquent\relations\HasOne;

class TblSubgrupo extends Model
{
    use HasFactory;

    protected $table = 'tbl_subgrupos';
    public function sede(): HasOne
    {
        return $this->hasOne(tbl_localidades_sede::class,'id','id_sede');
    }



}
