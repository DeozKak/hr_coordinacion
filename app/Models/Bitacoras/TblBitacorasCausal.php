<?php

namespace App\Models\Bitacoras;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblBitacorasCausal extends Model implements AuditableContract
{
    use HasFactory,auditableTrait;

    protected $table = 'tbl_bitacoras_causales';

    public $timestamps = false;

}
