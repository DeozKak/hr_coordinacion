<?php

namespace App\Models\Programacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class tbl_programacion_contrato extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;


    public function state(): BelongsTo
    {
        return $this->belongsTo(tbl_programacion_usuario::class,'id_programacion','id');

    }
}
