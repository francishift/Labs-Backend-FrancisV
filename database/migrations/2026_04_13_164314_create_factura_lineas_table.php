<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->text('concepto');
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('porcentaje_iva', 5, 2)->default(21);
            $table->decimal('porcentaje_irpf', 5, 2)->default(0);
            $table->decimal('total_linea', 10, 2)->default(0);
            $table->timestamps();
        });

        // Loop existing facturas and extract from raw_data
        DB::table('facturas')->orderBy('id')->chunk(100, function ($facturas) {
            foreach ($facturas as $factura) {
                if ($factura->raw_data) {
                    $rawData = json_decode($factura->raw_data, true);
                    $products = $rawData['products'] ?? [];
                    
                    foreach ($products as $p) {
                        $concepto = trim(($p['name'] ?? '') . "\n" . ($p['desc'] ?? ''));
                        // Skip empty products if any
                        if (empty($concepto) && ($p['price'] ?? 0) == 0) continue;
                        
                        $iva = $p['tax'] ?? 21;
                        $irpf = 0;
                        
                        // taxes is an array e.g. ["s_iva_21", "s_ret_15"]
                        if (isset($p['taxes']) && is_array($p['taxes'])) {
                            foreach ($p['taxes'] as $t) {
                                if (str_contains($t, 'ret_15')) $irpf = 15;
                                elseif (str_contains($t, 'ret_7')) $irpf = 7;
                            }
                        }

                        $totalLinea = floatval($p['price'] ?? 0) * floatval($p['units'] ?? 1);
                        
                        DB::table('factura_lineas')->insert([
                            'factura_id' => $factura->id,
                            'concepto' => $concepto,
                            'cantidad' => $p['units'] ?? 1,
                            'precio_unitario' => $p['price'] ?? 0,
                            'porcentaje_iva' => $iva,
                            'porcentaje_irpf' => $irpf,
                            'total_linea' => $totalLinea,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_lineas');
    }
};
