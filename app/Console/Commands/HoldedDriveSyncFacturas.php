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
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs invoices from Holded and uploads them to Google Drive if missing';

    /**
     * Execute the console command.
     */
    public function handle(HoldedService $holdedService, FacturaController $facturaController)
    {
        $year = $this->argument('year') ?? date('Y');
        $this->info("Starting invoice sync for year: {$year}");

        // 1. Sync DB from Holded
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
        $this->info("Synced {$count} invoices to local database.");

        // 2. Upload to Drive
        $facturas = Factura::whereBetween('date', [$start, $end])->get();
        $total = $facturas->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $uploaded = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($facturas as $factura) {
            // Idempotency: skip if already has Drive ID
            if ($factura->google_drive_file_id) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Upload
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
            'synced' => $count, // Invoices from Holded
            'processed' => $total, // Invoices in DB for the period
            'uploaded' => $uploaded,
            'skipped' => $skipped, // Already in Drive
            'errors' => $errors,
        ];

        $this->line("JSON_RESULT=" . json_encode($stats));

        return 0;
    }
}
