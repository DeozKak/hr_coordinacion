<?php

namespace App\Models\Zonificacion;

use Illuminate\Database\Eloquent\Model;

class AsignacionTecnicoLocalidad extends Model
{
    protected $table = 'tbl_asignacion_tecnicos_localidad';

    protected $fillable = [
        'localidad',
        'id_tecnico'
    ];
}
