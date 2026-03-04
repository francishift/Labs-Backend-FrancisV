<?php

namespace Tests\Feature\Admin;

use App\Models\Nota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Assuming we need a user with the 'admin' role, or we can just mock the role check if Spatie is used.
        // For simplicity, we'll create a user and act as them. If roles are needed:
        // $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_user_can_view_notas_index()
    {
        $user = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole('admin');
        
        $response = $this->actingAs($user)->get(route('admin.notas.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_create_a_nota()
    {
        $user = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post(route('admin.notas.store'), [
            'fecha' => '2030-01-01',
            'hora' => '10:00',
            'comentario' => 'Nota de prueba',
            'notificacion_minutos_antes' => 15,
        ]);

        $this->assertDatabaseHas('notas', [
            'user_id' => $user->id,
            'comentario' => 'Nota de prueba',
        ]);
    }

    public function test_user_can_update_their_own_nota()
    {
        $user = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole('admin');

        $nota = Nota::create([
            'user_id' => $user->id,
            'fecha' => '2030-01-01',
            'hora' => '10:00',
            'comentario' => 'Texto original',
            'notificacion_minutos_antes' => 0,
            'notificado' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('admin.notas.update', $nota->id), [
            'fecha' => '2030-01-01',
            'hora' => '11:00', // cambiamos la hora
            'comentario' => 'Texto actualizado',
            'notificacion_minutos_antes' => 15,
        ]);

        $this->assertDatabaseHas('notas', [
            'id' => $nota->id,
            'comentario' => 'Texto actualizado',
            'notificado' => 0, // se debe haber reseteado por cambiar hora
        ]);
    }

    public function test_user_cannot_update_others_nota()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $nota = Nota::create([
            'user_id' => $user1->id,
            'fecha' => '2030-01-01',
            'hora' => '10:00',
            'comentario' => 'Privado',
            'notificacion_minutos_antes' => 0,
        ]);

        // Evitamos el middleware de roles global para probar exclusivamente la política del controller
        $this->withoutMiddleware([\Spatie\Permission\Middlewares\RoleMiddleware::class]);

        $response = $this->actingAs($user2)->patch(route('admin.notas.update', $nota->id), [
            'fecha' => '2030-01-01',
            'hora' => '11:00',
            'comentario' => 'Hack',
            'notificacion_minutos_antes' => 15,
        ]);

        $response->assertStatus(403);
    }
}
