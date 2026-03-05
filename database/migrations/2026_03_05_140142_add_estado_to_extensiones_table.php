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
        Schema::table('extensiones', function (Blueprint $table) {
            $table->enum('estado', ['Activada', 'Cancelada'])->default('Activada')->after('tipo_licencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extensiones', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
