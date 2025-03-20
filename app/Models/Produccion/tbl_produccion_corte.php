<?php

namespace App\Models\Produccion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property mixed $fecha_inicio
 */
class tbl_produccion_corte extends Model
{
    public $timestamps = false;

    use HasFactory;

      public function corte(): HasOne
    {
        return $this->hasOne(TblProduccionHistorico::class, 'id_corte');
    }
}
