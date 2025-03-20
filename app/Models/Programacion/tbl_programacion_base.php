<?php

namespace App\Models\Programacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tbl_programacion_base extends Model
{
    use HasFactory;

    protected $table = 'tbl_programacion_base';

    public static $rules = [
        'NUMERO_ORDEN' => 'unique:tbl_programacion_base',
    ]; // Reglas de validación para campos únicos
}
