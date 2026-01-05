<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyecto',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'presupuesto',
        'estado',
        'client_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'presupuesto' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class);
    }

    public function extensiones()
    {
        return $this->belongsToMany(Extension::class, 'proyecto_extension');
    }
}
