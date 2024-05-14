<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class tbl_bitacora_archivo extends Model
{
    use HasFactory;

    public function Usuario(): BelongsTo
    {
        return $this->belongsTo(User::class ,'id_usuario','id');
    }
}
