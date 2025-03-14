<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\tblGrupo;

class TblGrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        tblGrupo::insert([
            ['grupo' => 'CE', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'CO', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'E', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'ES', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'NE', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'NO', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'NR1', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'NR2', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'NR3', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'NR4', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'NR8', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'NRE', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'ON', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'OS', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'S', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'SR1', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'SR2', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'SR3', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'SR4', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'SR5', 'created_at' => now(), 'updated_at' => now()],
            ['grupo' => 'SS', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
