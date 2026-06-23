<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\PurchaseFactura;
use App\Services\GeminiInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Mockery;

class PurchaseFacturaAITest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $geminiMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);

        $this->mockGoogleDriveService();

        // 1. Inyectamos nuestro Mock directamente al Service Container
        $this->geminiMock = Mockery::mock(GeminiInvoiceService::class);
        $this->app->instance(GeminiInvoiceService::class, $this->geminiMock);
    }

    protected function mockGoogleDriveService()
    {
        // Dummy Files system
        $filesMock = Mockery::mock();
        $filesMock->shouldReceive('listFiles')->andReturnSelf();
        $filesMock->shouldReceive('getFiles')->andReturn([]);
        $filesMock->shouldReceive('delete')->andReturn(true);
        
        $fileResponseMock = Mockery::mock();
        $fileResponseMock->shouldReceive('getId')->andReturn('dummy-folder-id');
        $filesMock->shouldReceive('create')->andReturn($fileResponseMock);
        
        $fileResponseMock2 = Mockery::mock();
        $fileResponseMock2->shouldReceive('getParents')->andReturn(['parent1']);
        $filesMock->shouldReceive('get')->andReturn($fileResponseMock2);
        
        $filesMock->shouldReceive('update')->andReturn(true);

        // Dummy Service
        $driveServiceMock = Mockery::mock(\Google\Service\Drive::class);
        $driveServiceMock->files = $filesMock;

        // Dummy Adapter & Disk
        $adapterMock = Mockery::mock();
        $adapterMock->shouldReceive('getService')->andReturn($driveServiceMock);

        $diskMock = Mockery::mock();
        $diskMock->shouldReceive('getAdapter')->andReturn($adapterMock);

        Storage::shouldReceive('disk')
            ->with('google_facturas')
            ->andReturn($diskMock);
    }

    public function test_upload_factura_happy_path()
    {
        // Creamos un Dummy PDF
        $file = UploadedFile::fake()->create('factura_prueba.pdf', 100, 'application/pdf');

        // Configuramos la respuesta de la IA
        $this->geminiMock->shouldReceive('extractInvoiceData')
            ->once()
            ->andReturn([
                'invoice_id' => 'INV-2024-001',
                'supplier_name' => 'Apple Store',
                'net_amount' => 1000,
                'tax_amount' => 210,
                'total_amount' => 1210,
                'irpf_amount' => 0,
                'invoice_date' => '2024-05-15',
                'raw' => ['extra' => 'info']
            ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.purchase-facturas.store'), [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        
        $this->assertDatabaseHas('purchase_facturas', [
            'number' => 'INV-2024-001',
            'provider_name' => 'Apple Store',
            'status' => 'recibida',
            'total' => 1210
        ]);
    }

    public function test_upload_factura_detects_duplicate_and_creates_dup_flag()
    {
        $file = UploadedFile::fake()->create('factura_prueba2.pdf', 100, 'application/pdf');

        // Insertamos previamente la factura en DB
        $originalFactura = PurchaseFactura::create([
            'number' => 'INV-2024-999',
            'provider_name' => 'Amazon',
            'date' => '2024-05-15',
            'status' => 'pagado',
            'total' => 50
        ]);

        // La IA intenta sobreescribir el mismo numero `INV-2024-999`
        $this->geminiMock->shouldReceive('extractInvoiceData')
            ->once()
            ->andReturn([
                'invoice_id' => 'INV-2024-999',
                'supplier_name' => 'Amazon Nuevo',
                'net_amount' => 40,
                'tax_amount' => 10,
                'total_amount' => 50,
                'irpf_amount' => 0,
                'invoice_date' => '2024-05-15'
            ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.purchase-facturas.store'), [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Factura duplicada detectada.');

        // Debe existir con el prefijo DUP-
        $this->assertDatabaseHas('purchase_facturas', [
            'provider_name' => 'Amazon Nuevo',
            'status' => 'duplicada'
        ]);

        $duplicate = PurchaseFactura::where('status', 'duplicada')->first();
        $this->assertStringContainsString('DUP-', $duplicate->number);
        $this->assertStringContainsString('INV-2024-999', $duplicate->number);
    }

    public function test_confirm_overwrite_replaces_duplicate_and_force_deletes_old()
    {
        // Estado inicial
        $original = PurchaseFactura::create([
            'number' => 'INV-001',
            'provider_name' => 'Old Provider',
            'date' => '2024-01-01',
            'status' => 'recibida',
            'total' => 10
        ]);

        $duplicada = PurchaseFactura::create([
            'number' => 'DUP-123456-INV-001',
            'provider_name' => 'New Provider',
            'date' => '2024-01-01',
            'status' => 'duplicada',
            'total' => 20,
            'raw_data' => [
                'duplicate_of' => $original->id,
                'intended_number' => 'INV-001'
            ]
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.purchase-facturas.overwrite', $duplicada->id));

        $response->assertRedirect();
        
        // Verificamos DB final
        $this->assertDatabaseMissing('purchase_facturas', [
            'id' => $original->id
        ]);

        $this->assertDatabaseHas('purchase_facturas', [
            'id' => $duplicada->id,
            'number' => 'INV-001',
            'provider_name' => 'New Provider',
            'status' => 'recibida'
        ]);
    }

    public function test_update_status_updates_database_successfully()
    {
        $factura = PurchaseFactura::create([
            'number' => 'INV-005',
            'provider_name' => 'Test Provider',
            'date' => '2024-01-01',
            'status' => 'recibida',
            'total' => 100
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.purchase-facturas.update-status', $factura->id), [
                'status' => 'pagado'
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('purchase_facturas', [
            'id' => $factura->id,
            'status' => 'pagado'
        ]);
    }

    public function test_update_status_fails_on_invalid_status()
    {
        $factura = PurchaseFactura::create([
            'number' => 'INV-006',
            'provider_name' => 'Test Provider',
            'date' => '2024-01-01',
            'status' => 'recibida',
            'total' => 100
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.purchase-facturas.update-status', $factura->id), [
                'status' => 'invalido'
            ]);

        $response->assertSessionHasErrors(['status']);
    }
}
