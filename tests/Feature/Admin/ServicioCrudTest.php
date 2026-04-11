<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Servicio;
use App\Models\Proyecto;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class ServicioCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        $client = Client::factory()->create();
        $this->proyecto = Proyecto::factory()->create(['client_id' => $client->id]);

        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);
        $this->withoutVite();
    }

    public function test_admin_can_create_servicio()
    {
        $payload = [
            'servicio' => 'Diseño UX',
            'descripcion' => 'Diseño de interfaces',
            'proyecto_id' => $this->proyecto->id,
            'fecha' => Carbon::now()->format('Y-m-d'),
            'duracion_minutos' => 120,
            'precio' => 1500
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.servicios.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $this->assertDatabaseHas('servicios', [
            'servicio' => 'Diseño UX',
            'proyecto_id' => $this->proyecto->id,
            'precio' => 1500
        ]);
    }

    public function test_admin_can_update_servicio()
    {
        $servicio = Servicio::create([
            'servicio' => 'Old Service',
            'descripcion' => 'Desc',
            'proyecto_id' => $this->proyecto->id,
            'fecha' => Carbon::now()->format('Y-m-d'),
            'duracion_minutos' => 60,
            'precio' => 1000
        ]);

        $payload = [
            'servicio' => 'Updated Service',
            'descripcion' => 'Desc updated',
            'proyecto_id' => $this->proyecto->id,
            'fecha' => Carbon::now()->format('Y-m-d'),
            'duracion_minutos' => 90,
            'precio' => 2000
        ];

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.servicios.update', $servicio->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $this->assertDatabaseHas('servicios', [
            'id' => $servicio->id,
            'servicio' => 'Updated Service',
            'precio' => 2000
        ]);
    }

    public function test_admin_can_delete_servicio()
    {
        $servicio = Servicio::create([
            'servicio' => 'Test Service',
            'descripcion' => 'Desc',
            'proyecto_id' => $this->proyecto->id,
            'fecha' => Carbon::now()->format('Y-m-d'),
            'duracion_minutos' => 60,
            'precio' => 100
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.servicios.destroy', $servicio->id));

        $response->assertRedirect();
        
        $this->assertDatabaseMissing('servicios', [
            'id' => $servicio->id
        ]);
    }
}
