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
        Schema::table('purchase_facturas', function (Blueprint $table) {
            $table->string('google_drive_file_id')->nullable()->after('notes');
            $table->json('raw_data')->nullable()->after('google_drive_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_facturas', function (Blueprint $table) {
            $table->dropColumn(['google_drive_file_id', 'raw_data']);
        });
    }
};
