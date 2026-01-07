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
        'precio_hora',
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
        return $this->belongsToMany(Extension::class, 'proyecto_extension')->withPivot('precio_aplicado')->withTimestamps();
    }

    /**
     * Sincroniza extensiones capturando su precio actual como snapshot.
     */
    public function syncExtensionSnapshots(array $extensionIds)
    {
        $extensionesConPrecio = [];
        foreach ($extensionIds as $extId) {
            $ext = Extension::find($extId);
            if ($ext) {
                $extensionesConPrecio[$extId] = ['precio_aplicado' => $ext->precio];
            }
        }
        return $this->extensiones()->sync($extensionesConPrecio);
    }

    /**
     * Datos para el gráfico de proyectos activos.
     */
    public static function getActiveDataForChart()
    {
        return self::where('estado', 'En proceso')
            ->select('proyecto', 'presupuesto')
            ->get()
            ->map(fn($p) => [
                'name' => $p->proyecto,
                'value' => (float) $p->presupuesto
            ]);
    }

    /**
     * Estadísticas de proyectos finalizados para el dashboard.
     */
    public static function getFinishedStats($year)
    {
        $query = self::where('estado', 'Finalizado')
            ->where(function ($q) use ($year) {
                $q->whereYear('fecha_fin', $year)
                    ->orWhere(function ($sub) use ($year) {
                        $sub->whereNull('fecha_fin')->whereYear('updated_at', $year);
                    });
            });

        return [
            'count' => $query->count(),
            'presupuesto' => $query->sum('presupuesto')
        ];
    }

    /**
     * Estadísticas de proyectos activos.
     */
    public static function getActiveStats()
    {
        return [
            'count' => self::where('estado', 'En proceso')->count(),
            'presupuesto' => self::where('estado', 'En proceso')->sum('presupuesto')
        ];
    }
}
