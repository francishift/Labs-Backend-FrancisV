<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Proyecto;
use App\Models\Extension;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoPdfMail;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ProyectoCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        $this->client = Client::factory()->create();
        
        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);
        $this->withoutVite();
    }

    public function test_admin_can_create_proyecto()
    {
        $extension = Extension::factory()->create();
        $factura = Factura::create([
            'holded_id' => 'abcde123',
            'contact' => 'abc',
            'date' => time(),
            'subtotal' => 10,
            'tax_amount' => 2,
            'total' => 12,
            'status' => 1,
            'raw_data' => []
        ]);

        $payload = [
            'proyecto' => 'Proyecto Nuevo',
            'descripcion' => 'Desarrollo web',
            'fecha_inicio' => Carbon::now()->format('Y-m-d'),
            'presupuesto' => 10000,
            'estado' => 'En proceso',
            'client_id' => $this->client->id,
            'extensiones' => [$extension->id],
            'facturas' => [$factura->id],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.proyectos.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $this->assertDatabaseHas('proyectos', [
            'proyecto' => 'Proyecto Nuevo',
            'client_id' => $this->client->id,
            'estado' => 'En proceso'
        ]);

        $proyecto = Proyecto::first();
        
        $this->assertDatabaseHas('facturas', [
            'id' => $factura->id,
            'proyecto_id' => $proyecto->id
        ]);
        
        $this->assertDatabaseHas('proyecto_extension', [
            'proyecto_id' => $proyecto->id,
            'extension_id' => $extension->id
        ]);
    }

    public function test_admin_can_update_proyecto()
    {
        $proyecto = Proyecto::factory()->create([
            'proyecto' => 'Old Name',
            'client_id' => $this->client->id,
            'estado' => 'En proceso'
        ]);

        $payload = [
            'proyecto' => 'Updated Name',
            'fecha_inicio' => $proyecto->fecha_inicio,
            'fecha_fin' => Carbon::now()->format('Y-m-d'),
            'estado' => 'Finalizado',
            'client_id' => $this->client->id,
        ];

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.proyectos.update', $proyecto->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $this->assertDatabaseHas('proyectos', [
            'id' => $proyecto->id,
            'proyecto' => 'Updated Name',
            'estado' => 'Finalizado'
        ]);
    }

    public function test_admin_can_delete_proyecto()
    {
        $proyecto = Proyecto::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.proyectos.destroy', $proyecto->id));

        $response->assertRedirect();
        
        $this->assertDatabaseMissing('proyectos', [
            'id' => $proyecto->id
        ]);
    }

    public function test_admin_can_send_proyecto_pdf_via_email()
    {
        Mail::fake();

        $proyecto = Proyecto::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.proyectos.send-pdf', $proyecto->id), [
                'email' => 'test@example.com'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(ProyectoPdfMail::class, function ($mail) use ($proyecto) {
            return $mail->hasTo('test@example.com') &&
                   $mail->proyecto->id === $proyecto->id;
        });
    }
}
