<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
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

        static::deleting(function (User $user) {
            // Borramos sus dispositivos VPN para activar sus hooks de revocación
            $user->vpnDevices()->each(fn($device) => $device->delete());
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
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordSpanish($token));
    }

    public function vpnDevices()
    {
        return $this->hasMany(VpnDevice::class);
    }
}