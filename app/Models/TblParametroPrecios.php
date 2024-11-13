<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblParametroPrecios extends Model
{
    use HasFactory;

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
