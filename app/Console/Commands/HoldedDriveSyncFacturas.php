<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Factura;
use App\Http\Controllers\Admin\Holded\FacturaController;
use App\Services\HoldedService;

class HoldedDriveSyncFacturas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holded:drive-sync-facturas {year? : The year to sync (default: current year)}';

    /**
     * Descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Sincroniza facturas de Holded y las sube a Google Drive si faltan';

    /**
     * Execute the console command.
     */
    public function handle(HoldedService $holdedService, FacturaController $facturaController)
    {
        $year = $this->argument('year') ?? date('Y');
        $this->info("Starting invoice sync for year: {$year}");

        // 1. Sincronizar DB desde Holded
        $start = strtotime("{$year}-01-01 00:00:00");
        $end = strtotime("{$year}-12-31 23:59:59");
        
        $this->info("Fetching invoices from Holded API...");
        $syncResult = $holdedService->syncDocuments('invoice', [
            'starttmp' => $start,
            'endtmp' => $end,
        ]);

        if (!$syncResult['success']) {
            $this->error("Failed to sync from Holded: " . ($syncResult['error'] ?? 'Unknown error'));
            return 1;
        }

        $count = count($syncResult['data']);
        $this->info("Se han sincronizado {$count} facturas con la base de datos local.");

        // 2. Subir a Drive
        $facturas = Factura::whereBetween('date', [$start, $end])->get();
        $total = $facturas->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $uploaded = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($facturas as $factura) {
            // Idempotencia: omitir si ya tiene ID de Drive
            if ($factura->google_drive_file_id) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Subida
            try {
                $facturaController->ensureInDrive($factura, $factura->holded_id);
                $uploaded++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to upload invoice {$factura->holded_id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Drive sync completed.");
        
        $stats = [
            'synced' => $count, // Facturas de Holded
            'processed' => $total, // Facturas en DB para el periodo
            'uploaded' => $uploaded,
            'skipped' => $skipped, // Ya en Drive
            'errors' => $errors,
        ];

        $this->line("JSON_RESULT=" . json_encode($stats));

        return 0;
    }
}
