<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:generate-certificate-expiry')
    ->dailyAt('07:00')
    ->withoutOverlapping();

Schedule::command('cement:send-certificate-email-notifications')
    ->dailyAt('07:15')
    ->withoutOverlapping();

Schedule::command('system:backup')
    ->dailyAt('01:30')
    ->withoutOverlapping();

Schedule::command('system:backup-cleanup')
    ->weeklyOn(1, '02:30')
    ->withoutOverlapping();
