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
    'timeMin' => \Carbon\Carbon::now()->format('c'),
    'timeMax' => \Carbon\Carbon::now()->addDays(5)->format('c'),
];
$results = $service->events->listEvents($calendarId, $optParams);
foreach ($results->getItems() as $event) {
    if (strpos($event->getSummary(), 'Prueba') !== false || strpos($event->getSummary(), 'recurrente') !== false || strpos($event->getSummary(), 'notificacion') !== false) {
        $rem = $event->getReminders();
        echo "Event: " . $event->getSummary() . "\n";
        echo "Use Default: " . ($rem->getUseDefault() ? "Yes" : "No") . "\n";
        if ($rem->getOverrides()) {
            foreach ($rem->getOverrides() as $or) {
                echo "Minute: " . $or->getMinutes() . " | Method: " . $or->getMethod() . "\n";
            }
        }
    }
}
