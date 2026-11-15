<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// On shared hosting, invoke `php artisan schedule:run` every minute via cron.
Schedule::command('bosh:send-client-reminders')->everyMinute()->withoutOverlapping(5);
