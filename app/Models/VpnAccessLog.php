<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnAccessLog extends Model
{
    protected $fillable = [
        'user_id',
        'target_device_id',
        'action',
        'ip_address',
        'user_agent',
        'details',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
