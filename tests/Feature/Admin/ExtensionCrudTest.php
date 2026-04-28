<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Extension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ExtensionCrudTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user->assignRole($role);
        
        $this->actingAs($this->user);
    }

    public function test_crear_extension_exitosa_guarda_en_bd()
    {
        $this->withoutExceptionHandling();
        
        $response = $this->post(route('admin.extensiones.store'), [
            'nombre' => 'WP Rocket',
            'url' => 'https://wp-rocket.me',
            'descripcion' => 'Plugin de caché',
            'precio' => 49.00,
            'tipo_licencia' => 'Anual',
            'estado' => 'Activada',
        ]);

        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('extensiones', [
            'nombre' => 'WP Rocket',
            'precio' => 49.00,
            'estado' => 'Activada',
        ]);
    }

    public function test_falla_creacion_extension_sin_campos_obligatorios()
    {
        $response = $this->post(route('admin.extensiones.store'), [
            'nombre' => 'WP Rocket',
            // Falta 'precio'
            'tipo_licencia' => 'Anual',
            'estado' => 'Activada',
        ]);

        $response->assertSessionHasErrors('precio');
    }
}
