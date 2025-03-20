<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblNominaFechas extends Model
{
    use HasFactory;

    protected $table = 'tbl_nomina_fechas';

    protected $fillable = [
        'cantidad_proyectada',
        'fecha',
    ];

    public $timestamps = false;

}
