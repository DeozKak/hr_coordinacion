<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class tbl_programacion_usuario extends Model
{
    use HasFactory;

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class,'id','id_usuario');
    }
}
