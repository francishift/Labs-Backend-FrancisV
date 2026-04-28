<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Mantenimiento;
use App\Models\MantenimientoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class MantenimientoServicioCrudTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $mantenimiento;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user->assignRole($role);
        
        $this->actingAs($this->user);

        $client = Client::factory()->create();
        $this->mantenimiento = Mantenimiento::factory()->create([
            'client_id' => $client->id,
            'precio_hora' => 50,
        ]);
    }

    public function test_crear_tarea_mantenimiento_guarda_en_bd()
    {
        $this->withoutExceptionHandling();
        
        $response = $this->post(route('admin.mantenimiento-servicios.store'), [
            'mantenimiento_id' => $this->mantenimiento->id,
            'descripcion' => 'Actualización de plugins',
            'duracion_minutos' => 60,
            'fecha' => '2024-05-01',
        ]);

        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('mantenimiento_servicios', [
            'mantenimiento_id' => $this->mantenimiento->id,
            'descripcion' => 'Actualización de plugins',
            'duracion_minutos' => 60,
        ]);
    }

    public function test_falla_crear_tarea_sin_duracion()
    {
        $response = $this->post(route('admin.mantenimiento-servicios.store'), [
            'mantenimiento_id' => $this->mantenimiento->id,
            'descripcion' => 'Actualización de plugins sin minutos',
            'fecha' => '2024-05-01',
            // Falta duracion_minutos
        ]);

        $response->assertSessionHasErrors('duracion_minutos');
    }
}
