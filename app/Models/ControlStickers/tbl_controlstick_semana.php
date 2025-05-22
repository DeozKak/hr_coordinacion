<?php

namespace App\Models\ControlStickers;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class tbl_controlstick_semana extends Model implements AuditableContract
{
    use AuditableTrait;
    protected $table = 'tbl_controlstick_semana';



}
