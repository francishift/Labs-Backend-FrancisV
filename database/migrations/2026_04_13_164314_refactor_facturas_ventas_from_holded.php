<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new fields
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('number')->nullable()->unique()->after('id');
            $table->unsignedBigInteger('client_id')->nullable()->after('number');
            $table->integer('due_date')->nullable()->after('date');
            $table->text('notes')->nullable();
            $table->text('description')->nullable();
            
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
        });

        // 2. Data Migration
        $facturas = DB::table('facturas')->get();
        foreach($facturas as $f) {
            $client = null;
            if ($f->contact) {
                $client = DB::table('clients')->where('contact', $f->contact)->first();
                if (!$client) {
                    // Fallback JSON contains check is skipped if not possible easily, DB raw can do it
                    $client = DB::table('clients')
                        ->whereRaw('JSON_CONTAINS(secondary_contacts, ?)', ['"'.$f->contact.'"'])
                        ->first();
                }
            }
            if (!$client && $f->contact_name) {
                $client = DB::table('clients')->where('name', $f->contact_name)->first();
            }

            $rawData = json_decode($f->raw_data, true);
            $number = $rawData['docNumber'] ?? 'FV-HOLDED-' . $f->id;
            $dueDate = $rawData['dueDate'] ?? null;

            DB::table('facturas')->where('id', $f->id)->update([
                'client_id' => $client ? $client->id : null,
                'number' => $number,
                'due_date' => $dueDate,
            ]);
        }

        // 3. Drop Holded columns in facturas
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropUnique('facturas_holded_id_unique');
            $table->dropColumn(['holded_id', 'contact_id', 'contact_name', 'contact']);
        });

        // 4. Drop Holded columns in clients
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['contact', 'secondary_contacts']);
        });
    }

    public function down(): void
    {
        // Best effort rollback
        Schema::table('clients', function (Blueprint $table) {
            $table->string('contact')->nullable();
            $table->json('secondary_contacts')->nullable();
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->string('holded_id')->nullable();
            $table->string('contact_id')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact')->nullable();
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['number', 'client_id', 'due_date', 'notes', 'description']);
        });
    }
};
