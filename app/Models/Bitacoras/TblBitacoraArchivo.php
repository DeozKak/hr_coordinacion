<?php

namespace App\Models\Bitacoras;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblBitacoraArchivo extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;

    public function Usuario(): BelongsTo
    {
        return $this->belongsTo(User::class ,'id_usuario','id');
    }
}
