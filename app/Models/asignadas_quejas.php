<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class asignadas_quejas extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;
    protected $table = "asignadas_quejas";

    protected $fillable = [
        'NUMERO_ORDEN',
        'CONTRATO',
        'NUMERO_SOLICITUD',
        'TIPO_SOLICITUD',
        'DESCRIPCION_SOLICITUD',
        'CEDULA',
        'NOMBRE',
        'DESC_DEPART',
        'DESC_LOCALIDAD',
        'BARRIO',
        'DIRECCION',
        'GPS',
        'DESC_CATEGORIA',
        'COD_UNIDAD_OPER',
        'DESC_TIPO_TRABAJO',
        'FECHA_ASIGNACION',
        'OBSERVACION_SOLICITUD',
        'FECHA_CIERRE_ULTIMA',
        'OBSERVACIÓN_CIERRE_ULTIMA',
        'TIPO_TRABAJO_CIERRE_ULTIMA',
        'DESC_CAUSAL_CIERRE_ULTIMA',
        'FECHA_ASIGNACIÓN_ULTIMA',
        'OBSERVACIÓN_ASIGNACIÓN_ULTIMA',
        'GESTIÓN_ASIGNACIÓN_ULTIMA',
        'TIPO_TRABAJO_ASIGNACIÓN_ULTIMA',
        'MOTIVO_DE_PQR',
        'estado',
        'FECHA_LEGALIZACION',
        'DESC_CAUSAL_LEGALIZACION',
        'OBSERVACION_LEGALIZACION',
        'ASIGNADO',
        'RESPONSABLE',
        'FECHA_ASIGNADO',
        'SUPERVISOR',
        'RECEPCION',
        'OBSERVACION_GESTION',
        'FECHA_RECEPCION',
        'FECHA_SOLICITUD_CIERRE',
        'CODIGO_AUTORIZACION',
        'FECHA_RESPUESTA',
        'FECHA_LIMITE',
        'DIAS_FALTANTES',
        'INSTRUCCIONES_CAMPO',
        'OBSERVACION_SUPERVISOR'
    ];
}
