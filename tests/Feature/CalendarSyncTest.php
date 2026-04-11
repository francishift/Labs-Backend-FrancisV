<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Nota;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CalendarSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $calendarMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mockeamos el servicio que inyecta el Observer
        $this->calendarMock = Mockery::mock(GoogleCalendarService::class);
        $this->app->instance(GoogleCalendarService::class, $this->calendarMock);
    }

    public function test_nota_created_syncs_to_calendar_if_flag_is_true()
    {
        $user = \App\Models\User::factory()->create();
        
        $this->calendarMock->shouldReceive('createEvent')
            ->once()
            ->andReturn('mock-event-id-123');

        $nota = Nota::create([
            'client_id' => null, // O opcionalmente un factory client
            'user_id' => $user->id,
            'comentario' => 'Test Nota Sync',
            'fecha' => '2024-10-10',
            'hora' => '10:00:00',
            'sync_calendar' => true
        ]);

        $this->assertEquals('mock-event-id-123', $nota->fresh()->google_event_id);
    }

    public function test_nota_updated_does_not_sync_if_no_changes_to_sync_flags()
    {
        $user = \App\Models\User::factory()->create();
        $this->calendarMock->shouldReceive('createEvent')->never();
        
        $nota = clone Nota::create([
            'user_id' => $user->id,
            'comentario' => 'No sync',
            'fecha' => '2024-10-10',
            'hora' => '10:00:00',
            'sync_calendar' => false
        ]);

        // Cambiamos el comentario, no debería disparar createEvent porque no hay sync_calendar=true
        $nota->update(['comentario' => 'Comentario cambiado']);
        $this->assertNull($nota->fresh()->google_event_id);
    }

    public function test_nota_toggling_sync_on_creates_event_and_assigns_id()
    {
        // 1. Crear sin sync
        $user = \App\Models\User::factory()->create();
        $nota = clone Nota::create([
            'user_id' => $user->id,
            'comentario' => 'Content',
            'fecha' => '2024-10-10',
            'hora' => '10:00:00',
            'sync_calendar' => false
        ]);

        $this->assertNull($nota->google_event_id);

        // 2. Encender sync debe disparar createEvent
        $this->calendarMock->shouldReceive('createEvent')
            ->once()
            ->andReturn('mock-event-id-456');

        $nota->update(['sync_calendar' => true]);

        $this->assertEquals('mock-event-id-456', $nota->fresh()->google_event_id);
    }

    public function test_nota_toggling_sync_off_deletes_event()
    {
        $user = \App\Models\User::factory()->create();
        // Prepara el mock para que permita creación inicial silenciosa
        $this->calendarMock->shouldReceive('createEvent')
            ->once()
            ->andReturn('mock-id-init');
            
        $nota = Nota::create([
            'user_id' => $user->id,
            'comentario' => 'Content',
            'fecha' => '2024-10-10',
            'hora' => '10:00:00',
            'sync_calendar' => true
        ]);

        // 2. Apagar sync debe disparar deleteEvent
        $this->calendarMock->shouldReceive('deleteEvent')
            ->with('mock-id-init')
            ->once();

        $nota->update(['sync_calendar' => false]);
        
        $this->assertNull($nota->fresh()->google_event_id);
    }
}
