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
        
        // Force migration for sqlite in memory if tables are missing
        if (!\Illuminate\Support\Facades\Schema::hasTable('configuraciones')) {
            $this->artisan('migrate');
        }

        // Ensure the configuraciones table has at least some data to avoid errors in redirects/dashboard
        \App\Models\Configuracion::firstOrCreate(['key' => 'precio_hora'], ['value' => '60']);
        \App\Models\Configuracion::firstOrCreate(['key' => 'descuento_mantenimiento'], ['value' => '0']);

        // Seed roles if necessary for assignRole
        $this->seed(\Database\Seeders\RolesAndAdminSeeder::class);
        $this->admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first() 
                      ?: User::factory()->create()->assignRole('admin');
        
        $this->vpnService = app(VpnService::class);
    }

    /** @test */
    public function it_allocates_next_available_ip_correctly()
    {
        // User needed for VpnDevice
        $user = User::factory()->create();
        VpnDevice::factory()->create(['user_id' => $user->id, 'internal_ip' => '10.0.0.2']);
        VpnDevice::factory()->create(['user_id' => $user->id, 'internal_ip' => '10.0.0.3']);
        
        $nextIp = $this->vpnService->getNextAvailableIp();
        $this->assertEquals('10.0.0.4', $nextIp);
    }

    /** @test */
    public function it_reuses_ip_from_trashed_devices()
    {
        $user = User::factory()->create();
        $device = VpnDevice::factory()->create(['user_id' => $user->id, 'internal_ip' => '10.0.0.2']);
        $device->delete(); // Soft delete

        $nextIp = $this->vpnService->getNextAvailableIp();
        
        $this->assertEquals('10.0.0.2', $nextIp);
        // Verify it was force deleted
        $this->assertDatabaseMissing('vpn_devices', ['id' => $device->id]);
    }

    /** @test */
    public function vpn_middleware_restricts_access()
    {
        // Skip for now as it depends on route configuration
        $this->markTestSkipped('Middleware test depends on specific route assignments.');
    }

    /** @test */
    public function admin_can_create_vpn_device()
    {
        Process::fake();
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

    /** @test */
    public function admin_can_revoke_vpn_access()
    {
        Process::fake();
        $user = User::factory()->create();
        $device = VpnDevice::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->admin)->post(route('admin.vpn.destroy', $device->id));

        $response->assertStatus(302);
        // We use soft delete in the controller
        $this->assertSoftDeleted('vpn_devices', ['id' => $device->id]);

        $this->assertDatabaseHas('vpn_access_logs', [
            'user_id' => $this->admin->id,
            'action' => 'DELETE_SUCCESS_DB'
        ]);
    }
}
