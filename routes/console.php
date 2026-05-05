<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('app:ejecutadas-programacion')
    ->everyThreeHours()
    ->between('05:00', '18:00')
    ->days([
        \Illuminate\Console\Scheduling\Schedule::MONDAY,
        \Illuminate\Console\Scheduling\Schedule::TUESDAY,
        \Illuminate\Console\Scheduling\Schedule::WEDNESDAY,
        \Illuminate\Console\Scheduling\Schedule::THURSDAY,
        \Illuminate\Console\Scheduling\Schedule::FRIDAY,
        \Illuminate\Console\Scheduling\Schedule::SATURDAY,
    ]);

Schedule::command('app:ejecutadas-base')
    ->everyThreeHours()
    ->between('05:00', '18:00')
    ->days([
        \Illuminate\Console\Scheduling\Schedule::MONDAY,
        \Illuminate\Console\Scheduling\Schedule::TUESDAY,
        \Illuminate\Console\Scheduling\Schedule::WEDNESDAY,
        \Illuminate\Console\Scheduling\Schedule::THURSDAY,
        \Illuminate\Console\Scheduling\Schedule::FRIDAY,
        \Illuminate\Console\Scheduling\Schedule::SATURDAY,
    ]);

Schedule::command('app:dev_clean')->daily();

Schedule::command('app:actualizar_-stickers')->dailyAt('12:00');

Schedule::command('app:actualizar-asignacion-tec')->dailyAt('05:00');

Schedule::command('app:actualizar_zonificacion')->dailyAt('05:00');

Schedule::command('app:clean-table-bitacora-diaria')->dailyAt('06:00');

Schedule::command('app:tiempos_quejas')
    ->dailyAt('11:00')
    ->days([1, 2, 3, 4, 5, 6]);

Schedule::command('app:update_recepcion_quejas')
    ->everyThirtyMinutes()
    ->days([1, 2, 3, 4, 5, 6]);




