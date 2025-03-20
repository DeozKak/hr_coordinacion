<?php

namespace App\Models\Produccion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblInspeccionIndustrial extends Model
{
    use HasFactory;

    protected $table = 'tbl_inspeccion_industrial';

    protected $fillable = [
        'id',
        'fecha',
        'cantidad',
        'total',
        'metagyc',
        'metagdo'
    ];

    public $timestamps = false;
}
