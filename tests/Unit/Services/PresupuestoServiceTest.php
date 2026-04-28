<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Client;
use App\Models\Presupuesto;
use App\Services\PresupuestoService;
use App\Services\GoogleDriveDocumentService;
use App\Services\DocumentPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class PresupuestoServiceTest extends TestCase
{
    use RefreshDatabase;

    private PresupuestoService $presupuestoService;
    private $googleDriveMock;
    private $pdfServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->googleDriveMock = Mockery::mock(GoogleDriveDocumentService::class);
        $this->googleDriveMock->shouldReceive('uploadDocument')->andReturn('mock_drive_id');
        
        $this->pdfServiceMock = Mockery::mock(DocumentPdfService::class);
        $this->pdfServiceMock->shouldReceive('generatePresupuestoPdf')->andReturn('mock_pdf_content');

        $this->presupuestoService = new PresupuestoService($this->googleDriveMock, $this->pdfServiceMock);
    }

    public function test_crear_presupuesto_calcula_importes_correctamente()
    {
        $client = Client::factory()->create();

        $datos = [
            'client_id' => $client->id,
            'date' => '2024-05-10',
            'due_date' => '2024-06-10',
            'notes' => 'Test notes',
            'description' => 'Test desc',
            'lineas' => [
                [
                    'concepto' => 'Desarrollo web',
                    'cantidad' => 1,
                    'precio_unitario' => 1000,
                    'porcentaje_iva' => 21, // 210
                    'porcentaje_irpf' => 0,
                ],
                [
                    'concepto' => 'Descuento',
                    'cantidad' => 1,
                    'precio_unitario' => -100,
                    'porcentaje_iva' => 21, // -21
                    'porcentaje_irpf' => 0,
                ]
            ]
        ];

        // Ejecutar creación
        $presupuesto = $this->presupuestoService->crearPresupuesto($datos);

        // Validar base de datos cabecera
        $this->assertDatabaseHas('presupuestos', [
            'id' => $presupuesto->id,
            'client_id' => $client->id,
            'subtotal' => 900.00, // 1000 - 100
            'tax_amount' => 189.00, // 210 - 21
            'irpf_amount' => 0.00,
            'total' => 1089.00, // 900 + 189
            'status' => \App\Enums\PresupuestoStatus::PENDING->value,
        ]);

        // Validar base de datos líneas
        $this->assertCount(2, $presupuesto->lineas);
        
        $this->assertDatabaseHas('presupuesto_lineas', [
            'presupuesto_id' => $presupuesto->id,
            'concepto' => 'Desarrollo web',
            'total_linea' => 1000.00,
        ]);
    }

    public function test_actualizar_presupuesto_recalcula_importes()
    {
        $client = Client::factory()->create();
        
        $presupuesto = $this->presupuestoService->crearPresupuesto([
            'client_id' => $client->id,
            'date' => '2024-05-10',
            'lineas' => [
                [
                    'concepto' => 'Original',
                    'cantidad' => 1,
                    'precio_unitario' => 100,
                    'porcentaje_iva' => 0,
                    'porcentaje_irpf' => 0,
                ]
            ]
        ]);

        $this->assertEquals(100, $presupuesto->total);

        // Actualizamos con nuevos datos
        $datosActualizados = [
            'client_id' => $client->id,
            'date' => '2024-05-11',
            'status' => \App\Enums\PresupuestoStatus::APPROVED->value,
            'lineas' => [
                [
                    'concepto' => 'Modificado',
                    'cantidad' => 2,
                    'precio_unitario' => 50,
                    'porcentaje_iva' => 21,
                    'porcentaje_irpf' => 0,
                ]
            ]
        ];

        $presupuestoActualizado = $this->presupuestoService->actualizarPresupuesto($presupuesto, $datosActualizados);

        // Subtotal = 100, IVA = 21, Total = 121
        $this->assertEquals(100.00, $presupuestoActualizado->subtotal);
        $this->assertEquals(121.00, $presupuestoActualizado->total);
        $this->assertEquals(\App\Enums\PresupuestoStatus::APPROVED, $presupuestoActualizado->status);
        $this->assertCount(1, $presupuestoActualizado->lineas);
        
        $this->assertDatabaseHas('presupuesto_lineas', [
            'presupuesto_id' => $presupuestoActualizado->id,
            'concepto' => 'Modificado',
        ]);
        
        $this->assertDatabaseMissing('presupuesto_lineas', [
            'presupuesto_id' => $presupuestoActualizado->id,
            'concepto' => 'Original',
        ]);
    }
}
