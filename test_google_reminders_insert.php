<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$client = new Google\Client();
$client->setClientId(config('services.google.client_id'));
$client->setClientSecret(config('services.google.client_secret'));
$accessToken = $client->fetchAccessTokenWithRefreshToken(config('services.google.refresh_token'));
$client->setAccessToken($accessToken);
$service = new Google\Service\Calendar($client);
$calendarId = env('GOOGLE_CALENDAR_ID', 'primary');

$gEvent = new Google\Service\Calendar\Event([
    'summary' => 'Test PHP Reminders API',
    'start' => ['dateTime' => \Carbon\Carbon::now()->addHours(1)->format('c')],
    'end' => ['dateTime' => \Carbon\Carbon::now()->addHours(2)->format('c')],
]);

$remindersParam = new \Google\Service\Calendar\EventReminders();
$remindersParam->setUseDefault(false);
$remindersParam->setOverrides([
    ['method' => 'popup', 'minutes' => 0]
]);
$gEvent->setReminders($remindersParam);

$evt = $service->events->insert($calendarId, $gEvent);
echo "Event Created: " . $evt->getId() . "\n";
echo "Overrides inside returned event:\n";
$rem = $evt->getReminders();
var_dump($rem->getOverrides());

// Cleanup
$service->events->delete($calendarId, $evt->getId());
