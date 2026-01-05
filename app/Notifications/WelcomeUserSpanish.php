<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeUserSpanish extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $password;

    /**
     * Create a new notification instance.
     */
    public function __construct($password)
    {
        $this->password = $password;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        return (new MailMessage)
            ->subject('¡Bienvenido! Activa tu cuenta')
            ->greeting('¡Hola! Te damos la bienvenida.')
            ->line('Tu cuenta ha sido creada exitosamente. Aquí tienes tus credenciales de acceso:')
            ->line(new \Illuminate\Support\HtmlString('<strong>Email:</strong> ' . $notifiable->email))
            ->line(new \Illuminate\Support\HtmlString('<strong>Contraseña:</strong> ' . $this->password))
            ->line('Por favor, verifica tu correo electrónico para activar tu cuenta.')
            ->action('Activar Cuenta', $verificationUrl)
            ->line('Si no reconoces esta actividad, simplemente ignora este correo.')
            ->salutation('Saludos, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
