<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropUnique(['holded_id']);
        });
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn(['holded_id', 'contact_name', 'contact']);
        });
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->string('holded_id')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact')->nullable();
        });
    }
};
