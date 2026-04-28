<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarService;
use Google\Service\Calendar\Event as GoogleServiceEvent;
use Google\Service\Calendar\EventDateTime as GoogleEventDateTime;
use Exception;

class GoogleCalendarApiService
{
    private function getService(): GoogleCalendarService
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        
        $accessToken = $client->fetchAccessTokenWithRefreshToken(config('services.google.refresh_token'));
        if (!isset($accessToken['error'])) {
            $client->setAccessToken($accessToken);
        } else {
            throw new Exception("Error al autenticar con Google Calendar: " . ($accessToken['error_description'] ?? $accessToken['error']));
        }

        return new GoogleCalendarService($client);
    }

    private function getCalendarId(): string
    {
        return env('GOOGLE_CALENDAR_ID', 'primary');
    }

    public function listEvents(array $optParams)
    {
        $service = $this->getService();
        return $service->events->listEvents($this->getCalendarId(), $optParams);
    }

    public function insertEvent(GoogleServiceEvent $event, array $optParams = [])
    {
        $service = $this->getService();
        return $service->events->insert($this->getCalendarId(), $event, $optParams);
    }

    public function getEvent(string $eventId)
    {
        $service = $this->getService();
        return $service->events->get($this->getCalendarId(), $eventId);
    }

    public function updateEvent(string $eventId, GoogleServiceEvent $event, array $optParams = [])
    {
        $service = $this->getService();
        return $service->events->update($this->getCalendarId(), $eventId, $event, $optParams);
    }

    public function deleteEvent(string $eventId, array $optParams = [])
    {
        $service = $this->getService();
        return $service->events->delete($this->getCalendarId(), $eventId, $optParams);
    }
}
