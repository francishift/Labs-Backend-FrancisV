<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Models\CalendarEvent;

class CalendarReminder extends Notification
{
    use Queueable;

    protected $event;

    public function __construct(CalendarEvent $event)
    {
        $this->event = $event;
    }

    public function via($notifiable)
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray($notifiable)
    {
        $dateParam = $this->event->start_date ? $this->event->start_date->format('Y-m-d') : null;
        return [
            'icon' => 'calendar',
            'title' => 'Recordatorio de Calendario',
            'message' => $this->event->name . ($this->event->description ? ': ' . $this->event->description : ''),
            'url' => route('admin.calendar.index', $dateParam ? ['date' => $dateParam] : []),
            'type' => 'calendar_event',
        ];
    }

    public function toWebPush($notifiable, $notification)
    {
        $dateParam = $this->event->start_date ? $this->event->start_date->format('Y-m-d') : null;
        return (new WebPushMessage)
            ->title('Francis Valenzuela')
            ->icon('/logo-icono.png') 
            ->body($this->event->name . ($this->event->description ? ': ' . $this->event->description : ''))
            ->action('Ver Calendario', 'view_calendar')
            ->data(['url' => route('admin.calendar.index', $dateParam ? ['date' => $dateParam] : [])])
            ->vibrate([100, 50, 100]);
    }
}
