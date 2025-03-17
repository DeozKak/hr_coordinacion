<?php

namespace Database\Seeders;

use App\Models\TblEstadosVne;
use Illuminate\Database\Seeder;

class TblEstadosVneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TblEstadosVne::insert(
            [
                ['estado_vne' => 'Casa sola', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Predio desocupado', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Predio en construccion', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Predio demolido', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Aplazado por el usuario', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Usuario no autoriza', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'No esta el encargado', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Direccion no encontrada', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Certificada por OIA externo', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Medidor no existe','created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Suspendido por cartera', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Novedad bloqueante', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Programada', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'PENDIENTE POR INSPECCIONAR DE RED MATRIZ', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'MEDIDOR FRENADO', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'CERTIFICADA POR OIA EXTERNO', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Perdida', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'PREDIO EN CONSTRUCCION.', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'Menor de edad', 'created_at' => now(), 'updated_at' => now()],
                ['estado_vne' => 'MEDIDOR POR LITROS BORRADOS', 'created_at' => now(), 'updated_at' => now()]
            ]
        );
    }
}
