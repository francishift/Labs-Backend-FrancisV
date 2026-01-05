<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones';

    protected $fillable = [
        'key',
        'value',
        'descripcion',
    ];

    /**
     * Get setting value by key.
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key.
     */
    public static function set($key, $value, $descripcion = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'descripcion' => $descripcion]
        );
    }
}
