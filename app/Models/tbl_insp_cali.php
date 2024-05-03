<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class tbl_insp_cali extends Model
{
    use HasFactory;

    protected $table = 'tbl_insp_cali';

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class,'SUPERVISOR');
    }
}
