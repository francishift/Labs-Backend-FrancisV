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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('holded_id')->unique();
            $table->string('contact_id')->nullable(); // ID de la DB local
            $table->string('contact_name')->nullable();
            $table->string('contact')->nullable(); // ID de Contacto de Holded
            $table->integer('date')->index(); // Marca de tiempo
            $table->decimal('total', 15, 2)->default(0);
            $table->integer('status')->default(0);
            $table->json('raw_data')->nullable();
            $table->string('google_drive_file_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
