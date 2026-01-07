<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Software;
use App\Models\Configuracion;
use App\Models\Mantenimiento;
use App\Models\Proyecto;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $costeAnual = Software::getTotalAnual();
        $porcentaje = (float) Configuracion::get('porcentaje_software', 2);

        Mantenimiento::whereNull('coste_software_anual')->update([
            'coste_software_anual' => $costeAnual,
            'porcentaje_software' => $porcentaje
        ]);

        Proyecto::whereNull('coste_software_anual')->update([
            'coste_software_anual' => $costeAnual,
            'porcentaje_software' => $porcentaje
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Mantenimiento::whereNotNull('coste_software_anual')->update([
            'coste_software_anual' => null,
            'porcentaje_software' => null
        ]);

        Proyecto::whereNotNull('coste_software_anual')->update([
            'coste_software_anual' => null,
            'porcentaje_software' => null
        ]);
    }
};
