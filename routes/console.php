<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorios de citas: se ejecuta cada día a las 9:00 AM
//Schedule::command('citas:recordatorios')->dailyAt('09:00');

// Schedule::command('citas:recordatorios')
//     ->everyMinute()
//     ->appendOutputTo(storage_path('logs/recordatorios-schedule.log'));