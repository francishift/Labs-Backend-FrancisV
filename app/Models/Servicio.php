<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
    }

    protected $fillable = [
        'servicio',
        'descripcion',
        'proyecto_id',
        'fecha',
        'duracion_minutos',
        'precio',
        'precio_hora',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
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
