<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('app:ejecutadas-programacion')->hourly();

Schedule::command('app:dev_clean')->daily();

Schedule::command('app:actualizar_-stickers')->dailyAt('12:00');
