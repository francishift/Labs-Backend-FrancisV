<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected static function booted(): void
    {
        static::saving(function (self $user) {
            // Si se cambia el password (por cualquier vía), apagamos el flag.
            if ($user->isDirty('password')) {
                $user->must_change_password = false;
            }
        });
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        // si lo vas a setear al crear usuario desde el admin:
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        // Hemos implementado un correo de bienvenida personalizado en el UserController
        // que incluye la contraseña y el enlace de verificación.
        // Por tanto, suprimimos el envío automático del correo por defecto de Laravel
        // si acabamos de crear al usuario.
        if ($this->wasRecentlyCreated) {
            return;
        }

        // Para el botón "Reenviar verificación", usamos nuestra notificación en español.
        $this->notify(new \App\Notifications\VerifyEmailSpanish);
    }
}