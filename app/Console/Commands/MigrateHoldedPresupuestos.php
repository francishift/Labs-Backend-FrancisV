<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Presupuesto;
use App\Models\PresupuestoLinea;

class MigrateHoldedPresupuestos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presupuestos:migrate-holded';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates existing Holded presupuestos into pure native relational format.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de presupuestos Holded a formato nativo...");

        $presupuestos = Presupuesto::whereNotNull('raw_data')->get();
        $count = 0;

        foreach ($presupuestos as $presupuesto) {
            $data = $presupuesto->raw_data;
            if (!is_array($data)) continue;

            $number = $data['docNumber'] ?? null;
            $subtotal = $data['subtotal'] ?? ($data['total'] ?? 0);
            $total = $data['total'] ?? 0;
            
            $tax_amount = 0;
            $irpf_amount = 0;

            // Limpiamos iteraciones previas en caso de ejecutar el comando varias veces
            $presupuesto->lineas()->delete();

            if (isset($data['products']) && is_array($data['products'])) {
                foreach ($data['products'] as $product) {
                    $price = $product['price'] ?? 0;
                    $units = $product['units'] ?? 0;
                    $discountPct = $product['discount'] ?? 0;
                    $taxPct = $product['tax'] ?? 0;
                    
                    $retentionPct = $product['retention'] ?? 0;
                    if ($retentionPct == 0 && isset($product['taxes']) && is_array($product['taxes'])) {
                        foreach ($product['taxes'] as $taxStr) {
                            if (preg_match('/ret_([0-9.]+)/', $taxStr, $matches)) {
                                $retentionPct = (float) $matches[1];
                            }
                        }
                    }
                    
                    $lineTotal = $price * $units;
                    // Apply discount if logic had it, though typically line total is just price * units 
                    // and discount is applied later, but we will store net line total
                    if ($discountPct > 0) {
                        $lineTotal -= $lineTotal * ($discountPct / 100);
                    }
                    
                    $tax_amount += $lineTotal * ($taxPct / 100);
                    $irpf_amount += $lineTotal * ($retentionPct / 100);

                    // Insert line
                    PresupuestoLinea::create([
                        'presupuesto_id' => $presupuesto->id,
                        'concepto' => $product['name'] ?? ($product['desc'] ?? 'Servicio'),
                        'cantidad' => $units,
                        'precio_unitario' => $price,
                        'porcentaje_iva' => $taxPct,
                        'porcentaje_irpf' => $retentionPct,
                        'total_linea' => $lineTotal,
                    ]);
                }
            } else {
                $subtotal = $total;
            }

            // Actualizar campos nativos del presupuesto
            $presupuesto->update([
                'number' => $number,
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'irpf_amount' => $irpf_amount,
            ]);

            $count++;
            $this->line("Migrado: {$number} (ID BBDD: {$presupuesto->id}) con " . count($data['products'] ?? []) . " líneas.");
        }

        $this->info("¡Migración completada! Se han transformado {$count} presupuestos correctamente.");
    }
}
