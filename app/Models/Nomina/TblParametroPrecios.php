<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblParametroPrecios extends Model implements AuditableContract
{
    use HasFactory,auditableTrait;

    protected $table = 'tbl_parametro_precios';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'res_metro',
        'res_norte',
        'res_cauca',
        'com_metro',
        'com_norte',
        'com_cauca',
        'inspeccion_industrial',
    ];

    public $timestamps = false;

}
