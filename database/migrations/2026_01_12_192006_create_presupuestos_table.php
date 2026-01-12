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
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->string('holded_id')->unique();
            $table->string('contact_id')->nullable();
            $table->string('contact_name')->nullable();
            $table->json('contact')->nullable();
            $table->unsignedBigInteger('date')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->integer('status')->default(0);
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
