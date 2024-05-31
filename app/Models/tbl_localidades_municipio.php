<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class tbl_localidades_municipio extends Model
{
    use HasFactory;

    public $timestamps = false;
    public function sede(): BelongsTo
    {
        return $this->belongsTo(tbl_localidades_sede::class, 'id_sede', 'id');
    }
    public function zona(): BelongsTo
    {
        return $this->belongsTo(tbl_produccion_zona::class, 'id_zona', 'id');
    }
}
