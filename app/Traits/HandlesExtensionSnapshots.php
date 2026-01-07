<?php

namespace App\Traits;

use App\Models\Extension;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HandlesExtensionSnapshots
{
    /**
     * Relación con extensiones, incluyendo las eliminadas lógicamente y el snapshot de precio.
     */
    public function extensiones(): BelongsToMany
    {
        // Determinamos la tabla pivot basándonos en el nombre de la clase
        $pivotTable = strtolower(class_basename($this)) . '_extension';
        
        // Mantenimiento usa una clave foránea personalizada en su migración original
        $foreignKey = (class_basename($this) === 'Mantenimiento') ? 'mantenimiento_id' : null;

        return $this->belongsToMany(Extension::class, $pivotTable, $foreignKey)
            ->withTrashed()
            ->withPivot('precio_aplicado')
            ->withTimestamps();
    }

    /**
     * Sincroniza extensiones capturando su precio actual como snapshot.
     */
    public function syncExtensionSnapshots(array $extensionIds)
    {
        $extensionesConPrecio = [];
        foreach ($extensionIds as $extId) {
            $ext = Extension::withTrashed()->find($extId);
            if ($ext) {
                $extensionesConPrecio[$extId] = ['precio_aplicado' => $ext->precio];
            }
        }
        return $this->extensiones()->sync($extensionesConPrecio);
    }
}
