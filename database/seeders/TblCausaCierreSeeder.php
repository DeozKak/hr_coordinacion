<?php

namespace Database\Seeders;

use App\Models\Coordinacion\TblCausasCierre;
use Illuminate\Database\Seeder;

class TblCausaCierreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TblCausasCierre::insert([
            ['causa_cierre' => 'Gdo', 'created_at'=> now(), 'updated_at'=> now()],
            ['causa_cierre' => '12137 abierto', 'created_at'=> now(), 'updated_at'=> now()],
            ['causa_cierre' => 'En cartera', 'created_at'=> now(), 'updated_at'=> now()],
            ['causa_cierre' => 'Inconvenientes sistema', 'created_at'=> now(), 'updated_at'=> now()],
            ['causa_cierre' => 'Ya esta certificafo', 'created_at'=> now(), 'updated_at'=> now()],
            ['causa_cierre' => 'Digitacion', 'created_at'=> now(), 'updated_at'=> now()],
        ]);
    }
}
