<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TblSubgrupo;

class TblSubgrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TblSubgrupo::insert([
            // Grupo CE
            ['subgrupo' => 'CE1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CE2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CE3', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CE4', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CE5', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CO1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CO2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CO3', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CO4', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CO5', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'CO6', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'E1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'E2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'E3', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'E4', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'E5', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'ES1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'ES2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NE1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NE2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NE3', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NE4', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NO1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NO2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NO3', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NO4', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NO5', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'ON1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'ON2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'OS1', 'created_at' => now(), 'updated_at' => now()],//30
            ['subgrupo' => 'OS2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'S1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'S2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'S3', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'S4', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'S5', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SS1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SS2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NR', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NR1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NR2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NR3', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NR4', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NR7', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRB', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR01', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR02', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR03', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR05', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR06', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR07', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR09', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR10', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR11', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR12', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'SR13', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRN1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRN2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRN3', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRN4', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRN5', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRS', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NROR', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRC1', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRC2', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRR', 'created_at' => now(), 'updated_at' => now()],
            ['subgrupo' => 'NRA', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
