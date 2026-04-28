<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Mantenimiento;
use App\Models\Proyecto;
use App\Models\Servicio;
use App\Models\MantenimientoServicio;
use App\Models\Configuracion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

class ResumenHorasTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        Carbon::setTestNow(Carbon::create(2024, 6, 15)); // Congelamos en Junio 2024

        // Configuración base de la app para no depender de seeders
        Configuracion::set('precio_hora', 10); // $10/h
        Configuracion::set('descuento_mantenimiento', 10); // 10%
        
        // Creamos rol admin para acceder a la ruta (según control de usuarios estándar)
        // La creación real del Role y Permission se abstraen y prueban a fondo en RolesPermissionsTest
        $this->admin = User::factory()->create();
        
        // Nos aseguramos que Spatie no nos bloquee si aún no armamos todos los guards en test mode,
        // Al inyectar Auth directo podemos acceder si no hay middleware complejo.
        // Simularemos tener full control invocando actingAs antes de la ruta.
    }

    public function test_resumen_horas_index_renders_without_data()
    {
        // Forzaremos by-pass a los permisos en este test simple
        $this->withoutExceptionHandling();
        
        // Disable permission middleware just for reaching the controller in this test
        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);
        
        $response = $this->actingAs($this->admin)->get(route('admin.resumen-horas.index', ['year' => 2024]));

        $response->assertStatus(200);
        
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Horas/Index')
            ->has('resumenMensual')
            ->has('stats')
        );
    }

    public function test_resumen_horas_aggregates_passive_mantenimiento_income()
    {
        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);
        
        $client = Client::factory()->create(['name' => 'Tech Corp']);
        
        // Un mantenimiento mensual activo desde enero
        Mantenimiento::factory()->create([
            'client_id' => $client->id,
            'fecha_inicio' => '2024-01-01',
            'importe' => 200, // Cada mes agregará 200 al histórico
            'tipo_pago' => 'mensual',
            'estado' => 'en curso'
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.resumen-horas.index', ['year' => 2024]));
        
        // Al estar congelado en Junio, la iteración del index para "all months" en realidad evalua
        // los 12 meses porque `$maxMonth = 12`.
        // Significa que Enero a Diciembre registrarán cada uno un mes en `$resumenMensual`.
        // 12 meses * 200 = 2400 total
        
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Horas/Index')
            ->where('stats.total_mantenimientos', 2400)
            ->where('stats.total_facturado', 2400)
        );
    }

    public function test_resumen_horas_aggregates_project_income_on_last_month_only()
    {
        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);
        
        $client = Client::factory()->create();
        
        $proyecto = Proyecto::factory()->create([
            'client_id' => $client->id,
            'presupuesto' => 5000,
            'estado' => 'En proceso'
        ]);
        
        // Registramos un servicio en Febrero (Mes 2)
        Servicio::create([
            'proyecto_id' => $proyecto->id,
            'fecha' => '2024-02-15',
            'duracion_minutos' => 120, // 2 horas
            'servicio' => 'Análisis inicial',
        ]);
        
        // Registramos un servicio en Abril (Mes 4)
        Servicio::create([
            'proyecto_id' => $proyecto->id,
            'fecha' => '2024-04-10',
            'duracion_minutos' => 60, // 1 hora
            'servicio' => 'Desarrollo backend',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.resumen-horas.index', ['year' => 2024]));
        
        // El presupuesto fijo de 5000 solo debería impactar a Abril (el último mes con servicios), NO a Febrero.
        // Y el coste interno de las 3 horas totales (180 mins) debe aplicarse.
        // Si el precio hora base es 10. Coste: 180 / 60 * 10 = 30.
        
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Horas/Index')
            ->where('stats.total_horas', 3)
            ->where('stats.total_importe_horas', 30)
            ->where('stats.total_proyectos', 5000)
            ->where('stats.total_mantenimientos', 0)
        );
    }

    public function test_resumen_horas_filters_by_client_and_calculates_correctly()
    {
        $this->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->withoutMiddleware(\Spatie\Permission\Middleware\RoleMiddleware::class);
        
        $clientA = Client::factory()->create(['name' => 'Cliente A']);
        $clientB = Client::factory()->create(['name' => 'Cliente B']);
        
        Mantenimiento::factory()->create([
            'client_id' => $clientA->id,
            'fecha_inicio' => '2024-01-01',
            'importe' => 100, // Mensual = 1200
        ]);
        
        Mantenimiento::factory()->create([
            'client_id' => $clientB->id,
            'fecha_inicio' => '2024-01-01',
            'importe' => 300, // Mensual = 3600
        ]);

        // Consulta filtrada solo al Cliente A
        $response = $this->actingAs($this->admin)->get(route('admin.resumen-horas.index', [
            'year' => 2024,
            'client_id' => $clientA->id
        ]));
        
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Horas/Index')
            ->where('stats.total_mantenimientos', 1200)
        );
    }
}
