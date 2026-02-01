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
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Admin who performed action
            $table->foreignId('target_device_id')->nullable(); // Can be null if device is deleted
            $table->string('action'); // CREATED, DELETED, REVOKED, etc.
            $table->string('ip_address')->nullable();
            $table->text('details')->nullable(); // JSON or text for extra info (errors, etc)
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
