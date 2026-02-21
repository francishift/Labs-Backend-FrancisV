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
        Schema::create('vpn_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Administrador que realizó la acción
            $table->foreignId('target_device_id')->nullable(); // Puede ser nulo si el dispositivo se elimina
            $table->string('action'); // CREADO, ELIMINADO, REVOCADO, etc.
            $table->string('ip_address')->nullable();
            $table->text('details')->nullable(); // JSON o texto para información extra (errores, etc)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vpn_access_logs');
    }
};
