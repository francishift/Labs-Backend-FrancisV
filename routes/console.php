<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run')->dailyAt('01:00')->then(fn () => Artisan::call('backup:clean'));

Schedule::command('vpn:sync-handshakes')->everyMinute()->withoutOverlapping();
Schedule::command('vpn:sync-peers')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('calendar:send-reminders')->everyMinute()->withoutOverlapping();
Schedule::command('calendar:sync-upcoming')->twiceDaily(1, 13)->withoutOverlapping();
