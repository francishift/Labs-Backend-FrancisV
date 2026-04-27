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
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn(['notification_minutes_before', 'notified']);
            $table->json('reminders')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn('reminders');
            $table->integer('notification_minutes_before')->nullable();
            $table->boolean('notified')->default(false);
        });
    }
};
