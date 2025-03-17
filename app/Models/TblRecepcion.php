<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblRecepcion extends Model
{
    protected $table = 'tbl_recepcion';
    use HasFactory;

    protected $fillable = [
        'ordenTrabajo',
        'ordenExterna',
        'ccOperario',
        'numeroSolicitud',
        'contrato',
        'tipo',
        'idVne',
        'direccion',
        'numActa',
        'estadoRecepcion',
        'created_at',
        'updated_at',
    ];
}
