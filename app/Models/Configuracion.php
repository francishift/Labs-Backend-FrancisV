<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;
11: 
12:     protected static function booted()
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
    }

    protected $table = 'configuraciones';

    protected $fillable = [
        'key',
        'value',
        'descripcion',
    ];

    protected static array $cache = [];

    /**
     * Get setting value by key.
     */
    public static function get($key, $default = null)
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $setting = self::where('key', $key)->first();
        $value = $setting ? $setting->value : $default;
        
        self::$cache[$key] = $value;
        
        return $value;
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
