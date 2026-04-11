<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proyecto;
use App\Models\Mantenimiento;
use App\Models\Client;
use App\Models\Extension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_proper_chart_data()
    {
        // Freeze time to March 1st so that active monthly maintenances equal exactly 10 months of income
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2024, 3, 1));
        \Illuminate\Support\Facades\Cache::flush();

        // Seed roles if necessary or manually create
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        
        $user = User::factory()->create();
        $user->assignRole('admin'); 

        // Create some data
        $client = Client::factory()->create(['name' => 'Test Client']);
        Proyecto::factory()->create([
            'client_id' => $client->id,
            'proyecto' => 'Test Project',
            'presupuesto' => 5000,
            'estado' => 'En proceso'
        ]);
        
        Mantenimiento::factory()->create([
            'client_id' => $client->id,
            'aplicacion' => 'Test App',
            'importe' => 200,
            'tipo_pago' => 'mensual',
            'estado' => 'en curso'
        ]);

        // Create a finished project for the current year
        Proyecto::factory()->create([
            'client_id' => $client->id,
            'proyecto' => 'Finished Project',
            'presupuesto' => 3000,
            'estado' => 'Finalizado',
            'fecha_fin' => now()->format('Y-m-d')
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('stats', fn (Assert $stats) => $stats
                ->where('proyectos_en_proceso', 1)
                ->where('presupuesto_total_activo', 5000)
                ->etc()
            )
            ->has('charts.proyectos_activos', 1)
            ->has('charts.valor_mantenimientos', 1)
            ->has('charts.valor_por_cliente', 1)
            ->has('charts.valor_por_cliente.0', fn (Assert $item) => $item
                ->where('name', 'Test Client')
                // 5000 (En proceso) + 3000 (Finalizado este año) + (200 * 10) (Mantenimiento anualizado desde el mes actual - marzo=10 meses)
                ->where('value', fn ($val) => $val === 5000 + 3000 + (200 * 10)) 
            )
        );
    }
}
