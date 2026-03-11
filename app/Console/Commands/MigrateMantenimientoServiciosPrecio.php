<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MantenimientoServicio;
use App\Models\Mantenimiento;
use App\Models\Configuracion;
use Illuminate\Support\Facades\DB;

class MigrateMantenimientoServiciosPrecio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mantenimiento:migrate-servicios-precio {--force : Forzar la ejecución sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el precio de los servicios de mantenimiento históricos utilizando la configuración global actual';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando migración de precios para servicios de mantenimiento...');

        $precioGlobal = Mantenimiento::getDiscountedHourlyRate();
        
        $this->info("Precio Global (con descuento) actual a aplicar: {$precioGlobal}€/hora");

        if ($precioGlobal <= 0) {
            $this->error('El precio global calculado es 0 o negativo. Por favor, revisa la configuración global antes de ejecutar este comando.');
            return 1;
        }

        $count = DB::table('mantenimiento_servicios')->whereNull('precio_hora')->orWhereNotNull('id')->count();
        
        if ($this->option('force') || $this->confirm("¿Estás seguro de que deseas actualizar {$count} servicios de mantenimiento históricos a {$precioGlobal}€/hora?")) {
            $updated = DB::table('mantenimiento_servicios')->update(['precio_hora' => $precioGlobal]);
            $this->info("Migración completada con éxito. Se han actualizado {$updated} servicios.");
        } else {
            $this->info('Migración cancelada.');
        }

        return 0;
    }
}
