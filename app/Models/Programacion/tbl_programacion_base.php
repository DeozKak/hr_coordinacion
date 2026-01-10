<?php

namespace App\Models\Programacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tbl_programacion_base extends Model
{
    use HasFactory;

    protected $table = 'tbl_programacion_base';
    protected $fillable = [
        'NUMERO_ORDEN',
        'CONTRATO',
        'DESC_ESTADO_PROD',
        'NOMBRE',
        'DESC_LOCALIDAD',
        'BARRIO' ,
        'DIRECCION',
        'NOM_CATE',
        'ID_TIPO_TRABAJO',
        'ID_TECNICO',
        'FECHA_ASIGNACION',
        'ESTADO_RECEPCION',
        'FECHA_RECEPCION',
        'SEDE',
        'GRUPO',
        'SUB_GRUPO'
    ];
    public static $rules = [
        'NUMERO_ORDEN' => 'unique:tbl_programacion_base',
    ]; // Reglas de validación para campos únicos
}
