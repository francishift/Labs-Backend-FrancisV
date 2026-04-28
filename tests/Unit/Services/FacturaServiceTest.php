<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Client;
use App\Models\Proyecto;
use App\Models\Factura;
use App\Services\FacturaService;
use App\Services\GoogleDriveDocumentService;
use App\Services\DocumentPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class FacturaServiceTest extends TestCase
{
    use RefreshDatabase;

    private FacturaService $facturaService;
    private $googleDriveMock;
    private $pdfServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Evitamos interactuar con la nube o generar PDFs reales en las pruebas
        $this->googleDriveMock = Mockery::mock(GoogleDriveDocumentService::class);
        $this->googleDriveMock->shouldReceive('uploadDocument')->andReturn('mock_drive_id');
        
        $this->pdfServiceMock = Mockery::mock(DocumentPdfService::class);
        $this->pdfServiceMock->shouldReceive('generateFacturaPdf')->andReturn('mock_pdf_content');

        // Instanciamos el servicio con las dependencias mockeadas
        $this->facturaService = new FacturaService($this->googleDriveMock, $this->pdfServiceMock);
    }

    public function test_crear_factura_calcula_importes_correctamente()
    {
        $client = Client::factory()->create();
        $proyecto = Proyecto::factory()->create(['client_id' => $client->id]);

        $datos = [
            'client_id' => $client->id,
            'proyecto_id' => $proyecto->id,
            'date' => '2024-05-10',
            'due_date' => '2024-06-10',
            'notes' => 'Test notes',
            'description' => 'Test desc',
            'lineas' => [
                [
                    'concepto' => 'Línea 1',
                    'cantidad' => 2, // 2 x 100 = 200
                    'precio_unitario' => 100,
                    'porcentaje_iva' => 21, // 42
                    'porcentaje_irpf' => 15, // 30
                ],
                [
                    'concepto' => 'Línea 2',
                    'cantidad' => 1, // 1 x 50 = 50
                    'precio_unitario' => 50,
                    'porcentaje_iva' => 21, // 10.5
                    'porcentaje_irpf' => 0, // 0
                ]
            ]
        ];

        // Ejecutar creación
        $factura = $this->facturaService->crearFactura($datos);

        // Validar base de datos cabecera
        $this->assertDatabaseHas('facturas', [
            'id' => $factura->id,
            'client_id' => $client->id,
            'subtotal' => 250.00, // 200 + 50
            'tax_amount' => 52.50, // 42 + 10.5
            'irpf_amount' => 30.00, // 30 + 0
            'total' => 272.50, // 250 + 52.50 - 30
            'status' => \App\Enums\FacturaStatus::PENDING->value,
        ]);

        // Validar base de datos líneas
        $this->assertCount(2, $factura->lineas);
        $this->assertDatabaseHas('factura_lineas', [
            'factura_id' => $factura->id,
            'concepto' => 'Línea 1',
            'total_linea' => 200.00,
        ]);
    }

    public function test_actualizar_factura_recalcula_importes()
    {
        $client = Client::factory()->create();
        
        // Creamos la factura base
        $factura = $this->facturaService->crearFactura([
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

        $this->assertEquals(100, $factura->total);

        // Actualizamos con nuevos datos
        $datosActualizados = [
            'client_id' => $client->id,
            'date' => '2024-05-11',
            'status' => \App\Enums\FacturaStatus::PAID->value,
            'lineas' => [
                [
                    'concepto' => 'Modificado',
                    'cantidad' => 2,
                    'precio_unitario' => 100,
                    'porcentaje_iva' => 21,
                    'porcentaje_irpf' => 0,
                ]
            ]
        ];

        $facturaActualizada = $this->facturaService->actualizarFactura($factura, $datosActualizados);

        // Subtotal = 200, IVA = 42, Total = 242
        $this->assertEquals(200.00, $facturaActualizada->subtotal);
        $this->assertEquals(242.00, $facturaActualizada->total);
        $this->assertEquals(\App\Enums\FacturaStatus::PAID, $facturaActualizada->status);
        $this->assertCount(1, $facturaActualizada->lineas); // Aseguramos que la línea anterior se eliminó
        
        $this->assertDatabaseHas('factura_lineas', [
            'factura_id' => $facturaActualizada->id,
            'concepto' => 'Modificado',
            'total_linea' => 200.00,
        ]);
        
        $this->assertDatabaseMissing('factura_lineas', [
            'factura_id' => $facturaActualizada->id,
            'concepto' => 'Original',
        ]);
    }

    public function test_duplicar_factura_clona_importes_y_lineas()
    {
        $client = Client::factory()->create();
        
        $facturaOriginal = $this->facturaService->crearFactura([
            'client_id' => $client->id,
            'date' => '2024-05-10',
            'lineas' => [
                [
                    'concepto' => 'Clonar esto',
                    'cantidad' => 1,
                    'precio_unitario' => 500,
                    'porcentaje_iva' => 21,
                    'porcentaje_irpf' => 0,
                ]
            ]
        ]);

        $facturaClonada = $this->facturaService->duplicarFactura($facturaOriginal);

        $this->assertNotEquals($facturaOriginal->id, $facturaClonada->id);
        $this->assertNotEquals($facturaOriginal->number, $facturaClonada->number);
        
        $this->assertEquals($facturaOriginal->subtotal, $facturaClonada->subtotal);
        $this->assertEquals($facturaOriginal->total, $facturaClonada->total);
        
        $this->assertCount(1, $facturaClonada->lineas);
        $this->assertEquals('Clonar esto', $facturaClonada->lineas->first()->concepto);
    }
}
