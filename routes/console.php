<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// F5: offline detection from Redis heartbeats, one alert per transition.
Schedule::command('app:machine-watchdog')->everyMinute()->withoutOverlapping();

// F6: re-send unacknowledged paid starts once; flag stuck ones for review.
Schedule::command('app:reconcile-starts')->everyMinute()->withoutOverlapping();
