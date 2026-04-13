<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fecha',
        'hora',
        'comentario',
        'enlace_reunion',
        'notificacion_minutos_antes',
        'notificado',
        'sync_calendar',
        'google_event_id',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'notificado' => 'boolean',
        'sync_calendar' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
