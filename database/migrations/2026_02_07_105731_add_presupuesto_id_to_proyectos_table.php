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
        Schema::table('proyectos', function (Blueprint $table) {
            $table->unsignedBigInteger('presupuesto_id')->nullable()->after('client_id');
            $table->foreign('presupuesto_id')->references('id')->on('presupuestos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropForeign(['presupuesto_id']);
            $table->dropColumn('presupuesto_id');
        });
    }
};
