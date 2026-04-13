<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Presupuesto;
use App\Http\Controllers\Admin\PresupuestoController;

class DriveSyncPresupuesto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $presupuesto;

    /**
     * Create a new job instance.
     */
    public function __construct(Presupuesto $presupuesto)
    {
        $this->presupuesto = $presupuesto;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $controller = app(PresupuestoController::class);
        $controller->saveToDrive($this->presupuesto);
    }
}
