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
        Schema::table('presupuestos', function (Blueprint $table) {
            if (Schema::hasColumn('presupuestos', 'contact_id')) {
                $table->dropColumn('contact_id');
            }
            $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->nullOnDelete();
        });

        // Sync old records based on `contact` (Holded String ID)
        \DB::statement("
            UPDATE presupuestos p 
            JOIN clients c ON p.contact = c.contact 
            SET p.client_id = c.id 
            WHERE p.contact IS NOT NULL AND p.contact != '' AND c.contact IS NOT NULL AND c.contact != ''
        ");

        // Further fallback: Sync based on contact_name
        \DB::statement("
            UPDATE presupuestos p 
            JOIN clients c ON p.contact_name = c.name 
            SET p.client_id = c.id 
            WHERE p.client_id IS NULL AND p.contact_name IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
            $table->string('contact_id')->nullable()->after('holded_id');
        });
    }
};
