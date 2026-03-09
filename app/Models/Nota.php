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
    ];

    protected $casts = [
        'fecha' => 'date',
        'notificado' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
