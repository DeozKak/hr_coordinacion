<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblParametroSalAux extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;

    protected $table = 'tbl_parametro_sal_aux';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'salario_minimo',
        'auxilio_transporte',
        'salud',
        'pension',
        'arl',
        'caja',
        'prima',
        'cesantias',
        'intCesantias',
        'vacaciones'
    ];

}
