<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MantenimientoServicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'mantenimiento_id',
        'descripcion',
        'duracion_minutos',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }
}
