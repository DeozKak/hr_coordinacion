<?php

namespace App\Models\Programacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class tbl_programacion_contrato extends Model
{
    use HasFactory;


    public function state(): BelongsTo
    {
        return $this->belongsTo(tbl_programacion_usuario::class,'id_programacion','id');

    }
}
