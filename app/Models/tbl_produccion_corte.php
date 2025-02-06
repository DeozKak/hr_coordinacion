<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class tbl_produccion_corte extends Model
{
    public $timestamps = false;
    
    use HasFactory;
    
      public function corte(): HasOne
    {
        return $this->hasOne(TblProduccionHistorico::class, 'id_corte');
    }
}
