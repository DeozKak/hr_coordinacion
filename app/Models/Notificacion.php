<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'tbl_notificaciones'; // Nombre correcto de la tabla
    protected $fillable = ['nombre'];

    public function users()
    {
        return $this->belongsToMany(
            User::class,  // Modelo de Usuario
            'tbl_notifications_has_users', // Tabla pivote
            'id_notification', // Clave foránea en la tabla pivote que referencia a Notificacion
            'id_user' // Clave foránea en la tabla pivote que referencia a User
        );
    }
}
