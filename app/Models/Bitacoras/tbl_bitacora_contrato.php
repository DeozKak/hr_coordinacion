<?php

namespace App\Models\Bitacoras;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class tbl_bitacora_contrato extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;

    public function bitacora(): HasOne
    {
        return $this->hasOne(tbl_bitacora_archivo::class);
    }
}
