<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Mantenimiento;
use App\Models\MantenimientoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoPdfMail; // This was used for sendPdfEmail in MantenimientoController
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class MantenimientoCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        $this->client = Client::factory()->create();
        
        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);
        $this->withoutVite();
    }

    public function test_admin_can_create_mantenimiento()
    {
        $payload = [
            'client_id' => $this->client->id,
            'aplicacion' => 'WebApp',
            'fecha_inicio' => Carbon::now()->format('Y-m-d'),
            'importe' => 150,
            'tipo_pago' => 'mensual',
            'estado' => 'en curso'
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.mantenimientos.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $this->assertDatabaseHas('mantenimientos', [
            'aplicacion' => 'WebApp',
            'client_id' => $this->client->id,
            'importe' => 150,
            'estado' => 'en curso'
        ]);

        $mantenimiento = Mantenimiento::first();
        
        // Ensure precio was stored automatically
        $this->assertDatabaseHas('mantenimiento_precios', [
            'mantenimiento_id' => $mantenimiento->id,
            'importe' => 150
        ]);
    }

    public function test_admin_can_update_mantenimiento()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'aplicacion' => 'Old App',
            'client_id' => $this->client->id,
            'estado' => 'en curso',
            'importe' => 100,
            'tipo_pago' => 'mensual'
        ]);

        $payload = [
            'aplicacion' => 'Updated App',
            'fecha_inicio' => Carbon::now()->format('Y-m-d'),
            'importe' => 200,
            'tipo_pago' => 'mensual',
            'estado' => 'finalizado',
            'client_id' => $this->client->id,
        ];

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.mantenimientos.update', $mantenimiento->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $this->assertDatabaseHas('mantenimientos', [
            'id' => $mantenimiento->id,
            'aplicacion' => 'Updated App',
            'importe' => 200,
            'estado' => 'finalizado'
        ]);
        
        // Ensure a new price snapshot was inserted if importe changed
        $this->assertDatabaseHas('mantenimiento_precios', [
            'mantenimiento_id' => $mantenimiento->id,
            'importe' => 200
        ]);
    }

    public function test_admin_can_delete_mantenimiento()
    {
        $mantenimiento = Mantenimiento::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.mantenimientos.destroy', $mantenimiento->id));

        $response->assertRedirect();
        
        $this->assertDatabaseMissing('mantenimientos', [
            'id' => $mantenimiento->id
        ]);
    }

    public function test_admin_can_add_servicio_to_mantenimiento()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'client_id' => $this->client->id,
        ]);

        $payload = [
            'mantenimiento_id' => $mantenimiento->id,
            'fecha' => Carbon::now()->format('Y-m-d'),
            'duracion_minutos' => 120,
            'descripcion' => 'Actualizacion core'
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.mantenimiento-servicios.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('mantenimiento_servicios', [
            'mantenimiento_id' => $mantenimiento->id,
            'duracion_minutos' => 120
        ]);
    }
}
