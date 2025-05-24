<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Activitylog\Traits\CausesActivity;
class User extends Authenticatable implements AuditableContract
{
    use HasFactory, Notifiable, HasRoles, HasPermissions, AuditableTrait, CausesActivity;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type_id',
        'identification',
        'state',
        'login_attempts',
    ];

    protected $auditExclude = [
        'password',
        'remember_token',
        // Añade aquí cualquier otro campo que quieras excluir de la auditoría
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function notificationsMail()
    {
    return $this->belongsToMany(
        Notificacion::class,  // Modelo de Notificacion
        'tbl_notifications_has_users', // Tabla pivote
        'id_user', // Clave foránea en la tabla pivote que referencia a User
        'id_notification' // Clave foránea en la tabla pivote que referencia a Notificacion
    );
    }
}


