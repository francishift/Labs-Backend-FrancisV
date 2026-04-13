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
        Schema::table('factura_lineas', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('concepto');
        });

        Schema::table('presupuesto_lineas', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('concepto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factura_lineas', function (Blueprint $table) {
            $table->dropColumn('descripcion');
        });

        Schema::table('presupuesto_lineas', function (Blueprint $table) {
            $table->dropColumn('descripcion');
        });
    }
};
