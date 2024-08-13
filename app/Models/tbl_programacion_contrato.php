<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class tbl_programacion_contrato extends Model
{
    use HasFactory;


    public function state(): BelongsTo
    {
        return $this->belongsTo(tbl_programacion_usuario::class,'id_programacion','id');
    
    }
}
