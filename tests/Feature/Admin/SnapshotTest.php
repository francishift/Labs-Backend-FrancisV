<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Mantenimiento;
use App\Models\Extension;
use App\Models\Client;
use App\Models\Configuracion;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_mantenimiento_captures_price_snapshots()
    {
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $client = Client::factory()->create();
        $extension = Extension::factory()->create(['precio' => 50, 'tipo_licencia' => 'Anual']);
        
        Configuracion::set('precio_hora', 60);
        Configuracion::set('descuento_mantenimiento', 10); // 60 * 0.9 = 54

        $response = $this->actingAs($user)->post(route('admin.mantenimientos.store'), [
            'aplicacion' => 'Test Snapshot',
            'client_id' => $client->id,
            'fecha_inicio' => now()->toDateString(),
            'tipo_pago' => 'mensual',
            'importe' => 100,
            'estado' => 'en curso',
            'extensiones' => [$extension->id]
        ]);

        $response->assertStatus(302);
        
        $mantenimiento = Mantenimiento::first();
        $this->assertEquals(54, $mantenimiento->precio_hora);
        $this->assertEquals(50, $mantenimiento->extensiones->first()->pivot->precio_aplicado);

        // Change global prices
        Configuracion::set('precio_hora', 100);
        $extension->update(['precio' => 200]);

        // Snapshots should remain the same
        $mantenimiento->refresh();
        $this->assertEquals(54, $mantenimiento->precio_hora);
        $this->assertEquals(50, $mantenimiento->extensiones->first()->pivot->precio_aplicado);
    }
}
