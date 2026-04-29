<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\VpnDevice;
use App\Models\VpnAccessLog;
use App\Services\VpnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class VpnImplementationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $vpnService;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Process::fake();
        
        // Forzar la migración para sqlite en memoria si faltan las tablas
        if (!\Illuminate\Support\Facades\Schema::hasTable('configuraciones')) {
            $this->artisan('migrate');
        }

        // Asegurar que la tabla configuraciones tenga al menos algunos datos para evitar errores en redirecciones/dashboard
        \App\Models\Configuracion::firstOrCreate(['key' => 'precio_hora'], ['value' => '60']);
        \App\Models\Configuracion::firstOrCreate(['key' => 'descuento_mantenimiento'], ['value' => '0']);

        // Sembrar roles si es necesario para assignRole
        $this->seed(\Database\Seeders\RolesAndAdminSeeder::class);
        $this->admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first() 
                      ?: User::factory()->create()->assignRole('admin');
        
        $this->vpnService = app(VpnService::class);
    }

    public function test_it_allocates_next_available_ip_correctly()
    {
        // Usuario necesario para VpnDevice
        $user = User::factory()->create();
        VpnDevice::factory()->create(['user_id' => $user->id, 'internal_ip' => '10.0.0.2']);
        VpnDevice::factory()->create(['user_id' => $user->id, 'internal_ip' => '10.0.0.3']);
        
        $nextIp = $this->vpnService->getNextAvailableIp();
        $this->assertEquals('10.0.0.4', $nextIp);
    }

    public function test_it_reuses_ip_from_trashed_devices()
    {
        $user = User::factory()->create();
        $device = VpnDevice::factory()->create(['user_id' => $user->id, 'internal_ip' => '10.0.0.2']);
        $device->delete(); // Borrado lógico (soft delete)

        $nextIp = $this->vpnService->getNextAvailableIp();
        
        $this->assertEquals('10.0.0.2', $nextIp);
        // Verificar que fue borrado físicamente
        $this->assertDatabaseMissing('vpn_devices', ['id' => $device->id]);
    }

    public function test_vpn_middleware_restricts_access()
    {
        // 1. Definimos una ruta temporal en memoria solo para este test
        // que utilice el middleware de restricción de VPN
        \Illuminate\Support\Facades\Route::get('/_test-vpn', function () {
            return 'Acceso Permitido';
        })->middleware(\App\Http\Middleware\VpnIpRestriction::class);

        // 2. Probamos con una IP externa (ej. IP de casa o móvil)
        // Debería bloquear el acceso (HTTP 403 Forbidden)
        $responseDeny = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/_test-vpn');
        $responseDeny->assertStatus(403);

        // 3. Probamos con una IP interna del rango de la VPN (10.0.0.x)
        // Debería permitir el acceso (HTTP 200 OK)
        $responseAllow = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->get('/_test-vpn');
        $responseAllow->assertStatus(200);
        $responseAllow->assertSee('Acceso Permitido');
    }

    public function test_admin_can_create_vpn_device()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.vpn.store', $user), [
            'name' => 'Test Laptop'
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('vpn_devices', [
            'user_id' => $user->id,
            'name' => 'Test Laptop'
        ]);

        $this->assertDatabaseHas('vpn_access_logs', [
            'user_id' => $this->admin->id,
            'action' => 'CREATE_SUCCESS'
        ]);
    }

    public function test_admin_can_revoke_vpn_access()
    {
        $user = User::factory()->create();
        $device = VpnDevice::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->admin)->post(route('admin.vpn.destroy', $device->id));

        $response->assertStatus(302);
        // Usamos borrado lógico en el controlador
        $this->assertSoftDeleted('vpn_devices', ['id' => $device->id]);

        $this->assertDatabaseHas('vpn_access_logs', [
            'user_id' => $this->admin->id,
            'action' => 'DELETE_SUCCESS_DB'
        ]);
    }
}
