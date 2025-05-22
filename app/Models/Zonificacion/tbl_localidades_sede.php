<?php

namespace App\Models\Zonificacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class tbl_localidades_sede extends Model implements AuditableContract
{
    public $timestamps = false;
    use HasFactory, AuditableTrait;
}
