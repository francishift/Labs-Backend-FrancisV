<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proyecto;
use App\Models\Mantenimiento;
use App\Models\Client;
use App\Models\Servicio;
use App\Models\MantenimientoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

class ResumenHoraTest extends TestCase
{
    use RefreshDatabase;

    public function test_resumen_horas_renders_proper_data()
    {
        // Congelar el tiempo a mitad de año para garantizar que el loop pasivo genere 6 meses
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2024, 6, 15));

        // Crear roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        
        $user = User::factory()->create();
        $user->assignRole('admin');

        $client = Client::factory()->create(['name' => 'Test Client']);
        
        $proyecto = Proyecto::factory()->create([
            'client_id' => $client->id,
            'proyecto' => 'Test Project',
            'precio_hora' => 50,
            'presupuesto' => 1000,
        ]);
        
        $mantenimiento = Mantenimiento::factory()->create([
            'client_id' => $client->id,
            'aplicacion' => 'Test App',
            'precio_hora' => 45,
            'fecha_inicio' => now()->startOfYear(),
            'importe' => 200,
            'tipo_pago' => 'mensual'
        ]);

        // Crear servicios en el mes actual del año actual
        Servicio::create([
            'proyecto_id' => $proyecto->id,
            'servicio' => 'Tareas de backend',
            'descripcion' => 'Descripción de la tarea',
            'fecha' => now()->format('Y-m-d'),
            'duracion_minutos' => 120, // 2 horas
            'precio' => 0,
            'precio_hora' => null,
        ]);

        MantenimientoServicio::create([
            'mantenimiento_id' => $mantenimiento->id,
            'descripcion' => 'Actualización servidor',
            'fecha' => now()->format('Y-m-d'),
            'duracion_minutos' => 60, // 1 hora
            'precio_hora' => null,
        ]);

        // En este punto, el motor pasivo genera 12 meses obligatorios para el mantenimiento
        // Mantenimiento de 200€ x 12 meses = 2400€. Proyecto único = 1000€. Total = 3400€.

        $response = $this->actingAs($user)->get(route('admin.resumen-horas.index', ['year' => 2024]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Horas/Index')
            ->has('resumenMensual', 12) // Debe haber 12 meses instanciados por el mantenimiento pasivo (proyección)
            ->has('stats', fn (Assert $stats) => $stats
                ->where('total_horas', 3) // 120m + 60m = 3 horas generadas en el mes 6
                ->where('total_importe_horas', 145) // (2h * €50) + (1h * €45) = 145
                ->where('total_proyectos', 1000)
                ->where('total_mantenimientos', 2400) // 200€ al mes x 12 meses
                ->where('total_facturado', 3400) 
                ->where('promedio_mensual_facturado', round(3400 / 12, 2))
            )
        );

        // Test filtering by tipo_servicio (proyectos)
        // Solo debe haber 1 mes instanciado (Junio) porque los proyectos no inyectan meses pasivos
        $responseProyectos = $this->actingAs($user)->get(route('admin.resumen-horas.index', ['year' => 2024, 'tipo_servicio' => 'proyectos']));
        $responseProyectos->assertInertia(fn (Assert $page) => $page
            ->has('stats', fn (Assert $stats) => $stats
                ->where('total_horas', 2) 
                ->where('total_importe_horas', 100)
                ->where('total_proyectos', 1000)
                ->where('total_mantenimientos', 0)
                ->where('total_facturado', 1000)
                ->etc()
            )
        );

        // Test filtering by tipo_servicio (mantenimientos)
        $responseMantenimientos = $this->actingAs($user)->get(route('admin.resumen-horas.index', ['year' => 2024, 'tipo_servicio' => 'mantenimientos']));
        $responseMantenimientos->assertInertia(fn (Assert $page) => $page
            ->has('stats', fn (Assert $stats) => $stats
                ->where('total_horas', 1) 
                ->where('total_importe_horas', 45)
                ->where('total_proyectos', 0)
                ->where('total_mantenimientos', 2400) // 200€ al mes x 12 meses
                ->where('total_facturado', 2400)
                ->etc()
            )
        );

        // Test filtering by Client ID
        $responseClient = $this->actingAs($user)->get(route('admin.resumen-horas.index', ['year' => 2024, 'client_id' => $client->id]));
        $responseClient->assertInertia(fn (Assert $page) => $page
            ->has('stats', fn (Assert $stats) => $stats
                ->where('total_horas', 3) 
                ->where('total_importe_horas', 145)
                ->where('total_proyectos', 1000)
                ->where('total_mantenimientos', 2400)
                ->where('total_facturado', 3400)
                ->etc()
            )
        );
        
        // Test filtering by an empty/invalid Client ID that has no registers
        $client2 = Client::factory()->create(['name' => 'Empty Client']);
        $responseEmpty = $this->actingAs($user)->get(route('admin.resumen-horas.index', ['client_id' => $client2->id]));
        $responseEmpty->assertInertia(fn (Assert $page) => $page
            ->has('stats', fn (Assert $stats) => $stats
                ->where('total_horas', 0)
                ->where('total_importe_horas', 0)
                ->where('total_proyectos', 0)
                ->where('total_mantenimientos', 0)
                ->where('total_facturado', 0)
                ->etc()
            )
        );
    }

    public function test_resumen_horas_detalle_endpoint_returns_json_with_lazy_loaded_data()
    {
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2024, 6, 15));
        
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $client = Client::factory()->create(['name' => 'Test Client']);
        $proyecto = Proyecto::factory()->create([
            'client_id' => $client->id,
            'proyecto' => 'Test Project',
            'precio_hora' => 50,
        ]);

        Servicio::create([
            'proyecto_id' => $proyecto->id,
            'servicio' => 'Tareas de backend Lazy Loading',
            'fecha' => now()->format('Y-m-d'),
            'duracion_minutos' => 120,
            'precio_hora' => null,
        ]);

        $response = $this->actingAs($user)->getJson(route('admin.resumen-horas.detalle', [
            'month' => 6,
            'year' => 2024
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'detalle' => [
                '*' => ['id', 'fecha', 'tipo', 'nombre', 'cliente', 'descripcion', 'minutos', 'importe_horas']
            ]
        ]);
        
        $this->assertEquals('Tareas de backend Lazy Loading', $response->json('detalle.0.descripcion'));
    }
}
