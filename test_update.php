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

// Let's get the master event "Vino de la semana Arengalia"
$masterId = "_8cs48gpk8csj8b9j8p136b9k6p1j8b9p64p3ib9l84pk4h9m6kpkaha468";
$gEvent = $service->events->get($calendarId, $masterId);
echo "Master Summary Before: " . $gEvent->getSummary() . "\n";
// DONT UPDATE IT to avoid messing user data, just see if getting it works.
