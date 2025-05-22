<?php

namespace App\Models\Programacion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class tbl_programacion_usuario extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class,'id','id_usuario');
    }

    public function programacion(): HasMany
    {
        return $this->HasMany(tbl_programacion_contrato::class,'id','id_programacion');
    }

}
