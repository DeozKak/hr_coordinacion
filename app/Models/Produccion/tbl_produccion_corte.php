<?php

namespace App\Models\Produccion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
/**
 * @property mixed $fecha_inicio
 */
class tbl_produccion_corte extends Model implements AuditableContract
{
    public $timestamps = false;

    use HasFactory,AuditableTrait;

      public function corte(): HasOne
    {
        return $this->hasOne(TblProduccionHistorico::class, 'id_corte');
    }
}
