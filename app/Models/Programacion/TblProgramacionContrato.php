<?php

namespace App\Models\Programacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblProgramacionContrato extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;


    public function state(): BelongsTo
    {
        return $this->belongsTo(TblProgramacionUsuario::class,'id_programacion','id');

    }
}
