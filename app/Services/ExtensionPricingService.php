<?php

namespace App\Services;

use App\Models\Extension;
use App\Models\Proyecto;
use App\Models\Mantenimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExtensionPricingService
{
    /**
     * Recalcula el precio aplicado a una extensión específica, dividiéndolo
     * entre todos los proyectos "En proceso" y mantenimientos "en curso" que
     * la tengan asignada. Posteriormente, actualiza masivamente las tablas
     * pivote solo de esos registros activos.
     *
     * @param Extension $extension
     * @return void
     */
    public function recalculateForExtension(Extension $extension): void
    {
        try {
            // 1. Obtener IDs de proyectos activos que usan esta extensión
            $proyectosActivosIds = DB::table('proyecto_extension')
                ->join('proyectos', 'proyecto_extension.proyecto_id', '=', 'proyectos.id')
                ->where('proyecto_extension.extension_id', $extension->id)
                ->where('proyectos.estado', 'En proceso')
                ->pluck('proyectos.id')
                ->toArray();

            // 2. Obtener IDs de mantenimientos activos que usan esta extensión
            $mantenimientosActivosIds = DB::table('mantenimiento_extension')
                ->join('mantenimientos', 'mantenimiento_extension.mantenimiento_id', '=', 'mantenimientos.id')
                ->where('mantenimiento_extension.extension_id', $extension->id)
                ->where('mantenimientos.estado', 'en curso')
                ->pluck('mantenimientos.id')
                ->toArray();

            // 3. Contar el total de elementos activos
            $totalActivos = count($proyectosActivosIds) + count($mantenimientosActivosIds);

            // 4. Calcular el nuevo precio (precio base / total activos, o precio base si es 0)
            $nuevoPrecio = $totalActivos > 0 
                ? round($extension->precio / $totalActivos, 2) 
                : $extension->precio;

            // 5. Actualizar masivamente las tablas pivote (A TODOS los registros)
            DB::table('proyecto_extension')
                ->where('extension_id', $extension->id)
                ->update(['precio_aplicado' => $nuevoPrecio]);

            DB::table('mantenimiento_extension')
                ->where('extension_id', $extension->id)
                ->update(['precio_aplicado' => $nuevoPrecio]);

            Log::info("ExtensionPricingService: Recalculados precios para extensión ID {$extension->id}. Total activos (divisor): {$totalActivos}. Nuevo precio aplicado a todos: {$nuevoPrecio}");

        } catch (\Exception $e) {
            Log::error("Error recalculando extensiones para la ID {$extension->id}: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta el recálculo para todas las extensiones pasadas por array de IDs.
     * Muy útil para llamarlo cuando un proyecto actualiza sus extensiones asociadas de golpe.
     *
     * @param array $extensionIds
     * @return void
     */
    public function recalculateForMultiple(array $extensionIds): void
    {
        // Evitamos recalcular varias veces la misma extensión si se repiten IDs
        $uniqueIds = array_unique($extensionIds);

        foreach ($uniqueIds as $extId) {
            $extension = Extension::withTrashed()->find($extId);
            if ($extension) {
                $this->recalculateForExtension($extension);
            }
        }
    }

    /**
     * Recalcula los costes para absolutamente TODAS las extensiones existentes.
     * Utilizado principalmente para el comando inicial de Artisan o mantenimientos globales.
     *
     * @return void
     */
    public function recalculateAll(): void
    {
        $extensiones = Extension::withTrashed()->get();
        foreach ($extensiones as $ext) {
            $this->recalculateForExtension($ext);
        }
    }
}
