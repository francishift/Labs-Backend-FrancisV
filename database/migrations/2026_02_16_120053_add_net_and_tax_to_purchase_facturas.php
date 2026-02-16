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
            $table->decimal('net_amount', 15, 2)->after('total')->nullable();
            $table->decimal('tax_amount', 15, 2)->after('net_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_facturas', function (Blueprint $table) {
            $table->dropColumn(['net_amount', 'tax_amount']);
        });
    }
};
