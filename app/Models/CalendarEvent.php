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
        'is_all_day',
        'reminders',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_all_day' => 'boolean',
        'reminders' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
