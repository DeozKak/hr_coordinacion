<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('app:ejecutadas-programacion')->dailyAt('05:00');

Schedule::command('app:dev_clean')->daily();

Schedule::command('app:actualizar_-stickers')->dailyAt('12:00');

Schedule::command('app:actualizar-asignacion-tec')->dailyAt('20:00');

Schedule::command('app:actualizar_zonificacion')->dailyAt('00:00');

Schedule::command('app:clean-table-bitacora-diaria')->dailyAt('06:00');


