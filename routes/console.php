<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ночной бэкап базы в 23:00 по Могилёву (когда точно нет активности).
// Требует системного крона: * * * * * php artisan schedule:run
Schedule::command('db:backup')
    ->dailyAt('23:00')
    ->timezone('Europe/Minsk');
