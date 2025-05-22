<?php

namespace App\Models\Zonificacion;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class tbl_inspector_detalle extends Model implements AuditableContract
{
    use AuditableTrait;
   protected $table = 'tbl_inspector_detalle';



}
