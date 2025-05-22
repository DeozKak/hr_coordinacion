<?php

namespace App\Models;

use App\Models\Zonificacion\TblSubgrupo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class tbl_insp_cali extends Model implements AuditableContract
{
    use HasFactory;
    use AuditableTrait;
    protected $table = 'tbl_insp_cali';

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class,'SUPERVISOR');
    }

    public function detalles(): BelongsToMany
    {
        return $this->belongsToMany(
            TblSubgrupo::class,
            'tbl_inspector_detalle',
            'inspector_id',
            'detalle_id'
        );
    }

}
