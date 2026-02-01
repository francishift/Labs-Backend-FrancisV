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
        Schema::table('vpn_devices', function (Blueprint $table) {
            $table->timestamp('last_handshake_at')->nullable()->after('internal_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vpn_devices', function (Blueprint $table) {
            $table->dropColumn('last_handshake_at');
        });
    }
};
