<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblParametroSalAux extends Model
{
    use HasFactory;

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
