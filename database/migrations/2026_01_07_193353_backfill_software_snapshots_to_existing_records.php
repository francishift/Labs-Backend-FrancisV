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
        $softwares = DB::table('softwares')->whereIn('estado', ['Activa', 'activo'])->get();
        
        $costeAnual = $softwares->sum(function($s) {
            $precio = (float) $s->precio;
            $tipo = strtolower($s->tipo_licencia ?? '');
            if ($tipo === 'anual') return $precio;
            if ($tipo === 'mensual') return $precio * 12;
            return $precio;
        });

        $config = DB::table('configuraciones')->where('key', 'porcentaje_software')->first();
        $porcentaje = $config ? (float) $config->value : 2.0;

        DB::table('mantenimientos')
            ->whereNull('coste_software_anual')
            ->update([
                'coste_software_anual' => $costeAnual,
                'porcentaje_software' => $porcentaje
            ]);

        DB::table('proyectos')
            ->whereNull('coste_software_anual')
            ->update([
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
