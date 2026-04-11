<?php

namespace Tests\Feature\Admin\Holded;

use Tests\TestCase;
use App\Models\User;
use App\Models\Factura;
use App\Services\HoldedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Mockery;

class FacturaCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $holdedMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);
        $this->withoutVite();

        $this->holdedMock = Mockery::mock(HoldedService::class);
        $this->app->instance(HoldedService::class, $this->holdedMock);
    }

    public function test_admin_can_view_facturas_index()
    {
        // El index sincroniza facturas primero y luego renderiza
        $this->holdedMock->shouldReceive('syncDocuments')
            ->once()
            ->andReturn(['success' => true]);

        // Insert we have one dummy local factura to see if it renders
        Factura::create([
            'holded_id' => '12345',
            'contact' => 'abcde',
            'contact_name' => 'Cliente Holded',
            'date' => time(),
            'subtotal' => 100,
            'tax_amount' => 21,
            'total' => 121,
            'status' => 1,
            'raw_data' => []
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.holded.facturas.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Holded/Facturas/Index')
            ->has('facturas')
            ->has('totals')
        );
    }

    public function test_admin_can_sync_facturas_drive()
    {
        // Se llama mediante Artisan command The Artisan command must mock HoldedService and Drive
        // Artisan commands are harder to test synchronously via HTTP unless we mock Artisan Facade.
        
        \Illuminate\Support\Facades\Artisan::shouldReceive('call')
            ->once()
            ->with('holded:drive-sync-facturas', \Mockery::any())
            ->andReturn(0);
            
        \Illuminate\Support\Facades\Artisan::shouldReceive('output')
            ->once()
            ->andReturn('JSON_RESULT={"synced":1,"processed":1,"uploaded":1,"skipped":0,"errors":0}');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.holded.facturas.sync-drive'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
