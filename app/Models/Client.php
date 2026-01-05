<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cif_nif',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'zip_code',
        'province',
        'country',
        'excel_created_at',
    ];

    protected $casts = [
        'excel_created_at' => 'datetime',
    ];

    public function proyectos()
    {
        return $this->hasMany(Proyecto::class);
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class);
    }
}
