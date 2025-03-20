<?php

namespace App\Models\Bitacoras;

use App\Models\tbl_insp_cali;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class tbl_dv_insp extends Model
{
    use HasFactory;

    protected $table = 'tbl_dv_insp';
    public $timestamps = false;


    public function Inspector(): BelongsTo
    {
        return $this->belongsTo(tbl_insp_cali::class ,'INSPECTOR','id');
    }

    public function Supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class,'SUPERVISOR','id');
    }
}
