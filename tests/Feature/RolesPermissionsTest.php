<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RolesPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Creamos roles según logic_negocio.md (lower case for middleware)
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'coordinador']);
        Role::create(['name' => 'visor']);
    }

    public function test_admin_can_access_configurations()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Configuracion controller solo para admins
        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
    }

    public function test_visor_is_forbidden_from_configurations()
    {
        $visor = User::factory()->create();
        $visor->assignRole('visor');

        $response = $this->actingAs($visor)->get(route('admin.settings.index'));

        // Visor no tiene persmisos para esto
        $response->assertStatus(403);
    }

    public function test_coordinador_can_access_projects_but_not_configs()
    {
        $coordinador = User::factory()->create();
        $coordinador->assignRole('coordinador');

        // Puede ver proyectos
        $responseProjects = $this->actingAs($coordinador)->get(route('admin.proyectos.index'));
        $responseProjects->assertStatus(200);

        // NO puede ver configuraciones
        $responseConfigs = $this->actingAs($coordinador)->get(route('admin.settings.index'));
        $responseConfigs->assertStatus(403);
    }
}
