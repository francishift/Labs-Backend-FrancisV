<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class VpnDevice extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected static function booted()
    {
        static::deleted(function (VpnDevice $device) {
            app(\App\Services\VpnService::class)->removePeer($device);
        });
    }

    protected $fillable = [
        'user_id',
        'name',
        'public_key',
        'internal_ip',
        'last_handshake_at',
        'is_active',
        'last_connected_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_connected_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
