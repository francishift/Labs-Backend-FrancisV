<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $fillable = [
        'user_id',
        'google_event_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'reminders',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'reminders' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
