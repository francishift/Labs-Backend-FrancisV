<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExtensionPricingService;

class RecalculateExtensionPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extensions:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula el coste compartido (snapshots) de todas las extensiones para los proyectos y mantenimientos activos actuales.';

    /**
     * Execute the console command.
     */
    public function handle(ExtensionPricingService $pricingService)
    {
        $this->info('Iniciando recálculo masivo de costes de extensiones...');
        
        $pricingService->recalculateAll();
        
        $this->info('¡Recálculo completado con éxito! Todos los proyectos "En proceso" y mantenimientos "en curso" tienen ahora el coste dividido actualizado.');
    }
}
