<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Services\DocumentPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Spatie Permission setup para tests
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user->assignRole($role);
        
        $this->actingAs($this->user);
    }

    public function test_crear_cliente_con_datos_validos_lo_guarda_en_base_de_datos()
    {
        $response = $this->post(route('admin.clientes.store'), [
            'name' => 'Empresa de Prueba S.L.',
            'cif_nif' => 'B12345678',
            'email' => 'test@empresa.com',
        ]);

        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('clients', [
            'name' => 'Empresa de Prueba S.L.',
            'cif_nif' => 'B12345678',
            'email' => 'test@empresa.com',
        ]);
    }

    public function test_crear_cliente_falla_sin_nombre()
    {
        $response = $this->post(route('admin.clientes.store'), [
            'cif_nif' => 'B12345678',
        ]);

        $response->assertSessionHasErrors('name');
        
        $this->assertDatabaseMissing('clients', [
            'cif_nif' => 'B12345678',
        ]);
    }

    public function test_exportar_pdf_usa_el_servicio_adecuado()
    {
        $client = Client::factory()->create([
            'name' => 'Cliente PDF Test'
        ]);

        $pdfMock = Mockery::mock(DocumentPdfService::class);
        $domPdfMock = Mockery::mock();
        $domPdfMock->shouldReceive('stream')->once()->andReturn('stream_content');
        
        $pdfMock->shouldReceive('generateClientPdf')
            ->once()
            ->with(Mockery::on(function ($arg) use ($client) {
                return $arg->id === $client->id;
            }))
            ->andReturn($domPdfMock);

        $this->app->instance(DocumentPdfService::class, $pdfMock);

        $response = $this->get(route('admin.clientes.pdf', $client->id));
        
        $response->assertStatus(200);
    }
}
