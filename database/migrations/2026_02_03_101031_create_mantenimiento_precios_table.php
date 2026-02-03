<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mantenimiento_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->onDelete('cascade');
            $table->decimal('importe', 12, 2);
            $table->string('tipo_pago');
            $table->date('fecha_aplicacion');
            $table->timestamps();
        });

        // Backfill de datos existentes
        $mantenimientos = DB::table('mantenimientos')->get();
        foreach ($mantenimientos as $mantenimiento) {
            DB::table('mantenimiento_precios')->insert([
                'mantenimiento_id' => $mantenimiento->id,
                'importe' => $mantenimiento->importe,
                'tipo_pago' => $mantenimiento->tipo_pago,
                'fecha_aplicacion' => $mantenimiento->fecha_inicio ?: now()->startOfMonth()->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_precios');
    }
};
