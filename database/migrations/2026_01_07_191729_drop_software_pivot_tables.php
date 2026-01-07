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
        Schema::dropIfExists('mantenimiento_software');
        Schema::dropIfExists('proyecto_software');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertiremos la eliminación ya que los datos eran obsoletos y el sistema ha cambiado su lógica globalmente
    }
};
