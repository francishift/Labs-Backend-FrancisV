<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Presupuesto;

class FixHoldedContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holded:fix-contacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Intenta recuperar los IDs de contacto de Holded buscándolos en los presupuestos existentes por nombre de cliente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando clientes sin ID de contacto de Holded...');

        $clients = Client::whereNull('contact')
            ->orWhere('contact', '')
            ->get();

        if ($clients->isEmpty()) {
            $this->info('Todos los clientes tienen su ID de contacto correctamente asignado.');
            return;
        }

        $this->info("Se encontraron {$clients->count()} clientes sin ID. Intentando recuperar...");

        $fixed = 0;
        $notFound = 0;

        foreach ($clients as $client) {
            $this->output->write("Procesando '{$client->name}'... ");

            // Intentamos buscar un presupuesto que contenga el nombre del cliente en su raw_data
            // Usamos un like con %nombre% para ser flexibles
            $budget = Presupuesto::where('raw_data', 'like', '%' . $client->name . '%')
                ->whereNotNull('contact')
                ->where('contact', '!=', '')
                ->first();

            if ($budget) {
                $client->contact = $budget->contact;
                $client->save();
                $this->info("MATCH! ID recuperado: {$budget->contact}");
                $fixed++;
            } else {
                // Intento secundario: buscar por la primera parte del nombre (si es empresa larga)
                $parts = explode(' ', $client->name);
                if (count($parts) > 1 && strlen($parts[0]) > 3) {
                    $shortName = $parts[0] . ' ' . $parts[1];
                     $budget = Presupuesto::where('raw_data', 'like', '%' . $shortName . '%')
                        ->whereNotNull('contact')
                        ->where('contact', '!=', '')
                        ->first();
                    
                    if ($budget) {
                         $client->contact = $budget->contact;
                         $client->save();
                         $this->info("MATCH (fuzzy)! ID recuperado: {$budget->contact}");
                         $fixed++;
                         continue;
                    }
                }

                $this->warn("No se encontraron presupuestos coincidentes.");
                $notFound++;
            }
        }

        $this->newLine();
        $this->info("Proceso finalizado.");
        $this->info("Clientes corregidos: $fixed");
        $this->info("Clientes sin coincidencias: $notFound");
    }
}
