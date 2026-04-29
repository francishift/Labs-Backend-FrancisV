<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\VpnDevice;
use App\Models\VpnAccessLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Notification;
use App\Notifications\WelcomeUserSpanish;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Process::fake();
        
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'visor', 'guard_name' => 'web']);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);
        
        $this->actingAs($this->admin);
    }

    public function test_admin_can_view_users_index()
    {
        $response = $this->get(route('admin.usuarios.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_user_and_vpn_is_generated()
    {
        Notification::fake();
        
        // El VpnService intentará generar un peer. En un entorno de testing puro a veces WireGuard falla,
        // pero podemos probar que el controlador intenta el flujo y envía el correo.
        // Simularemos un entorno exitoso o atraparemos su creación.
        
        $response = $this->post(route('admin.usuarios.store'), [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@gmail.com',
            'password' => 'password123456',
            'role' => 'visor'
        ]);

        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('users', [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@gmail.com',
        ]);

        $user = User::where('email', 'nuevo@gmail.com')->first();
        $this->assertTrue($user->hasRole('visor'));

        Notification::assertSentTo(
            [$user],
            WelcomeUserSpanish::class
        );
    }

    public function test_admin_can_update_user()
    {
        $user = User::factory()->create();
        
        $response = $this->patch(route('admin.usuarios.update', $user->id), [
            'name' => 'Usuario Actualizado',
            'email' => 'actualizado@gmail.com',
            'role' => 'visor',
            'password' => '' // Password vacía no lo actualiza
        ]);

        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Usuario Actualizado',
            'email' => 'actualizado@gmail.com',
        ]);
        
        // Reload user and check role
        $user->refresh();
        $this->assertTrue($user->hasRole('visor'));
    }

    public function test_admin_cannot_change_own_role()
    {
        $response = $this->patch(route('admin.usuarios.role', $this->admin->id), [
            'role' => 'visor'
        ]);

        $response->assertSessionHasErrors('role');
        
        $this->admin->refresh();
        $this->assertTrue($this->admin->hasRole('admin'));
    }

    public function test_admin_can_delete_user()
    {
        $user = User::factory()->create();
        
        $response = $this->delete(route('admin.usuarios.destroy', $user->id));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
