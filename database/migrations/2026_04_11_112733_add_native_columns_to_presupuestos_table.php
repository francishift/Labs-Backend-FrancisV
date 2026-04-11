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
            $table->string('number')->nullable()->after('holded_id');
            $table->decimal('subtotal', 10, 2)->default(0)->after('total');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
            $table->decimal('irpf_amount', 10, 2)->default(0)->after('tax_amount');
            $table->text('notes')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn(['number', 'subtotal', 'tax_amount', 'irpf_amount', 'notes']);
        });
    }
};
