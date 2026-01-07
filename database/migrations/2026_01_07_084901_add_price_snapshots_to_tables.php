<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Añadir columnas a tablas principales
        Schema::table('proyectos', function (Blueprint $table) {
            $table->decimal('precio_hora', 10, 2)->nullable()->after('presupuesto');
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->decimal('precio_hora', 10, 2)->nullable()->after('precio');
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->decimal('precio_hora', 10, 2)->nullable()->after('importe');
        });

        Schema::table('mantenimiento_servicios', function (Blueprint $table) {
            $table->decimal('precio_hora', 10, 2)->nullable()->after('duracion_minutos');
        });

        // 2. Añadir columnas a tablas pivot
        Schema::table('proyecto_extension', function (Blueprint $table) {
            $table->decimal('precio_aplicado', 10, 2)->nullable()->after('extension_id');
        });

        Schema::table('mantenimiento_extension', function (Blueprint $table) {
            $table->decimal('precio_aplicado', 10, 2)->nullable()->after('extension_id');
        });

        // 3. BACKFILL DE DATOS EXISTENTES
        
        // Obtener configuración actual
        $currentPrecioHora = DB::table('configuraciones')->where('key', 'precio_hora')->value('value') ?: 50.00;
        $currentDescuentoMant = DB::table('configuraciones')->where('key', 'descuento_mantenimiento')->value('value') ?: 0;
        $precioHoraMant = $currentPrecioHora * (1 - ($currentDescuentoMant / 100));

        // Actualizar proyectos y sus servicios
        DB::table('proyectos')->update(['precio_hora' => $currentPrecioHora]);
        DB::table('servicios')->update(['precio_hora' => $currentPrecioHora]);

        // Actualizar mantenimientos y sus tareas
        DB::table('mantenimientos')->update(['precio_hora' => $precioHoraMant]);
        DB::table('mantenimiento_servicios')->update(['precio_hora' => $precioHoraMant]);

        // Actualizar precios en tablas pivot de extensiones
        $extensiones = DB::table('extensiones')->get(['id', 'precio']);
        foreach ($extensiones as $ext) {
            DB::table('proyecto_extension')
                ->where('extension_id', $ext->id)
                ->update(['precio_aplicado' => $ext->precio]);
            
            DB::table('mantenimiento_extension')
                ->where('extension_id', $ext->id)
                ->update(['precio_aplicado' => $ext->precio]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mantenimiento_extension', function (Blueprint $table) {
            $table->dropColumn('precio_aplicado');
        });

        Schema::table('proyecto_extension', function (Blueprint $table) {
            $table->dropColumn('precio_aplicado');
        });

        Schema::table('mantenimiento_servicios', function (Blueprint $table) {
            $table->dropColumn('precio_hora');
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropColumn('precio_hora');
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('precio_hora');
        });

        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn('precio_hora');
        });
    }
};
