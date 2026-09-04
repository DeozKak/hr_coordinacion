<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos con los que funciona la aplicación.
 *
 * Hasta ahora sólo existían en la base de cada entorno, creados a mano, así que
 * una instalación limpia —un compañero nuevo, una copia de trabajo, la
 * integración continua— arrancaba sin ellos y cualquier ruta con
 * CheckPermission rechazaba a todo el mundo.
 *
 * Es idempotente: usa firstOrCreate, de modo que correrlo sobre una base que ya
 * los tiene no duplica nada. No reparte permisos entre roles ni toca los que ya
 * estén asignados; eso se administra desde la pantalla de usuarios.
 */
class RolesYPermisosSeeder extends Seeder
{
    /** Los permisos que comprueban las rutas, tomados de la base en uso. */
    private const PERMISOS = [
        'cargar_PQRS',
        'cargue_tareas',
        'control_stickers',
        'coordinacion_pqrs',
        'generar_bitacoras',
        'generar_programacion',
        'gestion_inspectores',
        'gestion_nomina',
        'gestion_preoperacional',
        'gestion_usuarios',
        'mod_devoluciones',
        'mod_tecnicos',
        'reporte_produccion',
        'ver_bitacoras',
        'ver_coordinacion_RN',
        'ver_coordinacion_RP',
        'ver_PQRS',
        'ver_produccion',
        'ver_programacion',
        'ver_residente',
        'ver_supervisor',
    ];

    private const ROLES = [
        'admin',
        'Auxiliar_coordinacion',
        'Auxiliar_metrologia',
        'Auxiliar_programacion',
        'Coordinador_HSEQ',
        'Coordinador_RN',
        'Coordinador_RP',
        'Director',
        'PQRS',
        'Residente',
        'Supervisor',
        'user',
    ];

    public function run(): void
    {
        foreach (self::PERMISOS as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        /* Spatie cachea el mapa de permisos; sin esto, lo recién creado no se
           ve hasta la siguiente petición. */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
