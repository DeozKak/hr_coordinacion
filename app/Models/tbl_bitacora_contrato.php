<?php

namespace App\Models;

use App\Models\tbl_bitacora_archivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class tbl_bitacora_contrato extends Model
{
    use HasFactory;

    public function bitacora(): HasOne
    {
        return $this->hasOne(tbl_bitacora_archivo::class);
    }
}
