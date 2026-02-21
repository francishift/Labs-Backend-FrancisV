<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\HtmlString;

class WelcomeUserSpanish extends Notification
{
    use Queueable;

    public $password;
    public $vpnConfig;

    /**
     * Create a new notification instance.
     */
    public function __construct($password, $vpnConfig = null)
    {
        $this->password = $password;
        $this->vpnConfig = $vpnConfig;
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
        $mail = (new MailMessage)
            ->subject('¡Bienvenido! Acceso y configuración VPN');

        if ($this->vpnConfig) {
            $qrCode = (string) QrCode::format('png')->size(200)->generate($this->vpnConfig);

            $mail->markdown('emails.welcome-vpn-markdown', [
                'email' => $notifiable->email,
                'password' => $this->password,
                'qrCode' => $qrCode,
                'vpnConfig' => $this->vpnConfig,
                'appName' => config('app.name'),
            ])
            ->attachData($this->vpnConfig, 'vpn-acceso.conf', [
                'mime' => 'text/plain',
            ]);
        } else {
            // Fallback al formato anterior si no hay VPN config
            $mail->greeting('¡Hola! Te damos la bienvenida.')
                 ->line('Tu cuenta ha sido creada exitosamente. Aquí tienes tus credenciales de acceso a la plataforma:')
                 ->line('Email: ' . $notifiable->email)
                 ->line('Contraseña: ' . $this->password)
                 ->line('No se pudo generar tu archivo de configuración VPN en este momento. Por favor, contacta con tu administrador para obtener acceso.')
                 ->salutation('Saludos, ' . config('app.name'));
        }

        return $mail;
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
