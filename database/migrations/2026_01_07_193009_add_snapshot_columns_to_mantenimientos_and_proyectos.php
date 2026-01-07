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
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->decimal('porcentaje_software', 5, 2)->nullable()->after('precio_hora')->comment('Snapshot del porcentaje global en el momento de creación/actualización');
            $table->decimal('coste_software_anual', 12, 2)->nullable()->after('porcentaje_software')->comment('Snapshot del gasto total anual de software en el momento de creación/actualización');
        });

        Schema::table('proyectos', function (Blueprint $table) {
            $table->decimal('porcentaje_software', 5, 2)->nullable()->after('precio_hora')->comment('Snapshot del porcentaje global en el momento de creación/actualización');
            $table->decimal('coste_software_anual', 12, 2)->nullable()->after('porcentaje_software')->comment('Snapshot del gasto total anual de software en el momento de creación/actualización');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_software', 'coste_software_anual']);
        });

        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_software', 'coste_software_anual']);
        });
    }
};
