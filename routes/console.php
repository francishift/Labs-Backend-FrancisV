<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run')->dailyAt('01:00')->then(fn () => Artisan::call('backup:clean'));

Schedule::command('vpn:sync-handshakes')->everyMinute();
Schedule::command('vpn:sync-peers')->everyFiveMinutes();
Schedule::command('calendar:send-reminders')->everyMinute();
