<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('app:check-idle')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo('/var/www/canberra-api.deenlytics.com/storage/logs/check-idle.log'); // Use absolute string path

Schedule::command('payment:warnings')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo('/var/www/canberra-api.deenlytics.com/storage/logs/payment-warnings.log'); // Use absolute string path

Schedule::command('location:clean')
    ->dailyAt('23:59')
    ->withoutOverlapping()
    ->appendOutputTo('/var/www/canberra-api.deenlytics.com/storage/logs/location-clean.log'); // Use absolute string path
