<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Models\Nota;

class NotaRecordatorio extends Notification
{
    use Queueable;

    protected $nota;

    public function __construct(Nota $nota)
    {
        $this->nota = $nota;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Francis Valenzuela')
            ->icon('/logo-icono.png') 
            ->body($this->nota->comentario)
            ->action('Ver Nota', 'view_nota')
            ->data(['url' => route('admin.notas.edit', $this->nota->id)])
            ->vibrate([100, 50, 100]);
    }
}
