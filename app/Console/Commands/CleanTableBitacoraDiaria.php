<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanTableBitacoraDiaria extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-table-bitacora-diaria';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron job dedicado a limpiar table de bitácora diaria.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::table('tbl_bitacora_diaria')->truncate();
            Log::info('Tabla tbl_bitacora_diaria Limpiada con Exito');
        } catch (\Exception $e) {
            Log::error('Error al limpiar la tabla tbl_bitacora_diaria: ' . $e->getMessage());
        }
    }
}
