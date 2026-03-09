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
                // Se asigna el precio base inicialmente. El servicio de pricing
                // se encargará de sobreescribirlo matemáticamente en el paso siguiente 
                // solo si el modelo padre (Proyecto/Mto) está "activo".
                $extensionesConPrecio[$extId] = ['precio_aplicado' => $ext->precio];
            }
        }
        
        $result = $this->extensiones()->sync($extensionesConPrecio);

        // Recalcular dinámicamente el precio de todas las extensiones que han entrado o salido
        $affectedExtensionIds = array_merge($result['attached'], $result['detached'], $result['updated']);
        
        if (!empty($affectedExtensionIds)) {
            app(\App\Services\ExtensionPricingService::class)->recalculateForMultiple($affectedExtensionIds);
        }

        return $result;
    }
}
