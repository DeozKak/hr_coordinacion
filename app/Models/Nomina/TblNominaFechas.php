<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblNominaFechas extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;

    protected $table = 'tbl_nomina_fechas';

    protected $fillable = [
        'cantidad_proyectada',
        'fecha',
    ];

    public $timestamps = false;

}
