<?php

namespace Tests\Feature\Holded;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HoldedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_presupuestos_page()
    {
        $this->withoutVite();
        $this->seed(\Database\Seeders\RolesAndAdminSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin'); // Asegúrate de que el método assignRole exista o usa el sistema de roles del proyecto

        $start = date('Y-m-d', strtotime('-365 days'));
        $end = date('Y-m-d');
        $startTimestamp = strtotime($start . ' 00:00:00');
        $endTimestamp = strtotime($end . ' 23:59:59');

        Http::fake([
            "api.holded.com/api/invoicing/v1/documents/estimate*" => Http::response([
                [
                    'id' => '123',
                    'customId' => 'PRE-001',
                    'contactName' => 'Cliente de Prueba',
                    'date' => time(),
                    'total' => 1000,
                    'status' => 1,
                ]
            ], 200),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.holded.presupuestos.index', ['start' => $start, 'end' => $end]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Holded/Presupuestos/Index')
            ->has('presupuestos')
            ->where('errorMessage', null)
            ->where('filters.start', $start)
            ->where('filters.end', $end)
        );
    }
}
