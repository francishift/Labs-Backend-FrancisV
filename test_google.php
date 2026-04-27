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

$optParams = [
    'singleEvents' => true,
    'orderBy' => 'startTime',
    'timeMin' => \Carbon\Carbon::now()->subMonths(1)->format('c'),
    'timeMax' => \Carbon\Carbon::now()->addMonths(2)->format('c'),
];

$results = $service->events->listEvents($calendarId, $optParams);
foreach ($results->getItems() as $event) {
    if ($event->getRecurringEventId() || preg_match('/_([0-9]{8})(T[0-9]{6}Z)?$/', $event->getId())) {
        echo "ID: " . $event->getId() . " | Recurring: " . $event->getRecurringEventId() . " | Title: " . $event->getSummary() . "\n";
    }
}
