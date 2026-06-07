<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('app:check-idle')->everyFiveMinutes();

Schedule::command('payment:warnings')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(
        storage_path('logs/payment-warnings.log')
    );

Schedule::command('location:clean')
    ->dailyAt('23:59')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/location-clean.log'));
