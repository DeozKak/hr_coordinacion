<?php

namespace App\Console\Commands;
use App\Http\Controllers\StickersController;
use App\Models\ControlStickers\tbl_controlstick_semana;
use Illuminate\Console\Command;

class update_stickers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update_stickers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza base de stickers con las bitacoras';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ultimo_registro = tbl_controlstick_semana::latest()->first();
        $controller = new StickersController();
        $controller->getData($ultimo_registro->id);
    }
}
