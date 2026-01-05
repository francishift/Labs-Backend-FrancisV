<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'servicio',
        'descripcion',
        'proyecto_id',
        'fecha',
        'duracion_minutos',
        'precio',
    ];

    protected $casts = [
        'fecha' => 'date',
        'duracion_minutos' => 'integer',
        'precio' => 'decimal:2',
    ];

    /**
     * Get the proyecto that owns the servicio.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
