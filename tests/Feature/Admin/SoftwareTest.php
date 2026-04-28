<?php

namespace Tests\Feature\Admin;

use App\Models\Software;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftwareTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_software_index()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.softwares.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_create_software()
    {
        $data = [
            'tipo' => 'Software',
            'nombre' => 'Test Software',
            'descripcion' => 'Test Description',
            'tipo_licencia' => 'Anual',
            'precio' => 100.50,
            'estado' => 'Activa',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.softwares.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('softwares', ['nombre' => 'Test Software']);
    }

    public function test_admin_can_update_software()
    {
        $software = Software::factory()->create();

        $data = [
            'tipo' => 'Hosting',
            'nombre' => 'Updated Name',
            'descripcion' => 'Updated Description',
            'tipo_licencia' => 'Mensual',
            'precio' => 50.00,
            'estado' => 'Finalizada',
        ];

        $response = $this->actingAs($this->admin)->patch(route('admin.softwares.update', $software), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('softwares', ['nombre' => 'Updated Name', 'tipo' => 'Hosting']);
    }

    public function test_admin_can_delete_software()
    {
        $software = Software::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.softwares.destroy', $software));

        $response->assertRedirect();
        $this->assertSoftDeleted('softwares', ['id' => $software->id]);
    }

    public function test_falla_creacion_software_sin_campos_obligatorios()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.softwares.store'), [
            'tipo' => 'Software',
            // Falta 'nombre'
            'tipo_licencia' => 'Anual',
            'precio' => 150.00,
            'estado' => 'Activa',
        ]);

        $response->assertSessionHasErrors('nombre');
    }
}
