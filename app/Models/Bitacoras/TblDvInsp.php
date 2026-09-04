<?php

namespace App\Models\Bitacoras;

use App\Models\TblInspCali;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblDvInsp extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;

    protected $table = 'tbl_dv_insp';
    public $timestamps = false;


    public function Inspector(): BelongsTo
    {
        return $this->belongsTo(TblInspCali::class ,'INSPECTOR','id');
    }

    public function Supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class,'SUPERVISOR','id');
    }
}
