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
        Schema::table('mantenimiento_servicios', function (Blueprint $table) {
            $table->date('fecha')->nullable()->after('mantenimiento_id');
        });

        // Optional: Populate existing records with their created_at date
        DB::table('mantenimiento_servicios')->update(['fecha' => DB::raw('DATE(created_at)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mantenimiento_servicios', function (Blueprint $table) {
            $table->dropColumn('fecha');
        });
    }
};
