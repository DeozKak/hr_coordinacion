<?php

namespace App\Models\Produccion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class TblInspeccionIndustrial extends Model implements AuditableContract
{
    use HasFactory,AuditableTrait;

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
