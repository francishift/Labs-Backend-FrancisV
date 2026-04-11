<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Presupuesto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Mockery;

class PresupuestoNativeTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        // Evitamos middleware de Spatie por simplicidad del scope de testeo
        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);
        
        $this->mockGoogleDriveService();
    }

    protected function mockGoogleDriveService()
    {
        // Creamos un dummy de \Google\Service\Drive\Resource\Files
        $filesMock = Mockery::mock();
        $filesMock->shouldReceive('listFiles')->andReturnSelf();
        $filesMock->shouldReceive('getFiles')->andReturn([]);
        
        $fileResponseMock = Mockery::mock();
        $fileResponseMock->shouldReceive('getId')->andReturn('dummy-folder-id');
        $filesMock->shouldReceive('create')->andReturn($fileResponseMock);

        // Dummy Service
        $driveServiceMock = Mockery::mock(\Google\Service\Drive::class);
        $driveServiceMock->files = $filesMock;

        // Dummy Adapter & Disk
        $adapterMock = Mockery::mock();
        $adapterMock->shouldReceive('getService')->andReturn($driveServiceMock);

        $diskMock = Mockery::mock();
        $diskMock->shouldReceive('getAdapter')->andReturn($adapterMock);

        Storage::shouldReceive('disk')
            ->with('google_presupuestos')
            ->andReturn($diskMock);
    }

    public function test_create_presupuesto_happy_path_calculates_totals()
    {
        $client = Client::factory()->create(['name' => 'Cliente Test']);

        $payload = [
            'client_id' => $client->id,
            'contact_name' => 'Contacto Demo',
            'date' => '2024-05-10',
            'due_date' => '2024-06-10',
            'lineas' => [
                [
                    'concepto' => 'Línea de Servicio 1',
                    'cantidad' => 2,
                    'precio_unitario' => 50, // subtotal = 100
                    'porcentaje_iva' => 21,  // iva = 21
                    'porcentaje_irpf' => 15  // irpf = 15
                ],
                [
                    'concepto' => 'Línea 2',
                    'cantidad' => 1,
                    'precio_unitario' => 100, // subtotal = 100
                    'porcentaje_iva' => 0,    // 0
                    'porcentaje_irpf' => 0    // 0
                ]
            ],
            'notes' => 'Notas',
            'description' => 'Desc'
        ];

        // 200 de Subtotal
        // + 21 de IVA
        // - 15 de IRPF
        // = 206 de Total

        $response = $this->actingAs($this->admin)->post(route('admin.presupuestos.store'), $payload);

        $response->assertRedirect(route('admin.presupuestos.index'));
        
        $this->assertDatabaseHas('presupuestos', [
            'client_id' => $client->id,
            'contact_name' => 'Contacto Demo',
            'subtotal' => 200,
            'tax_amount' => 21,
            'irpf_amount' => 15,
            'total' => 206
        ]);
    }

    public function test_create_presupuesto_validation_fails_on_empty_lines()
    {
        $client = Client::factory()->create();

        $payload = [
            'client_id' => $client->id,
            'contact_name' => 'Contacto Demo',
            'date' => '2024-05-10',
            // Omitimos lineas intencionalmente
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.presupuestos.store'), $payload);

        $response->assertSessionHasErrors(['lineas']);
    }

    public function test_create_presupuesto_validation_protects_against_negative_amounts()
    {
        $client = Client::factory()->create();

        $payload = [
            'client_id' => $client->id,
            'contact_name' => 'Contacto Demo',
            'date' => '2024-05-10',
            'lineas' => [
                [
                    'concepto' => 'Error',
                    'cantidad' => -1, // No permitido min:0.01
                    'precio_unitario' => 50,
                    'porcentaje_iva' => -21, // No permitido min:0
                    'porcentaje_irpf' => 0
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.presupuestos.store'), $payload);

        $response->assertSessionHasErrors(['lineas.0.cantidad', 'lineas.0.porcentaje_iva']);
    }
}
