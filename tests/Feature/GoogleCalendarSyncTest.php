<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Nota;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Mockery\MockInterface;

class GoogleCalendarSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we bypass any authentication/role issues for basic model logic tests if needed, 
        // but here we are primarily testing Eloquent Observers reacting to Model events.
    }

    public function test_creating_a_note_syncs_with_google_calendar_if_enabled()
    {
        $this->mock(GoogleCalendarService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createEvent')
                 ->once()
                 ->andReturn('mocked-google-id-1234');
        });

        $nota = Nota::create([
            'user_id' => User::factory()->create()->id,
            'comentario' => 'Reunión de prueba', 
            'fecha' => now()->format('Y-m-d'),
            'hora' => '10:00:00',
            'sync_calendar' => true,
        ]);

        $this->assertEquals('mocked-google-id-1234', $nota->google_event_id);
    }

    public function test_creating_a_note_does_not_sync_if_disabled()
    {
        $this->mock(GoogleCalendarService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('createEvent');
            $mock->shouldNotReceive('updateEvent');
            $mock->shouldNotReceive('deleteEvent');
        });

        $nota = Nota::create([
            'user_id' => User::factory()->create()->id,
            'comentario' => 'Reunión sin sync',
            'fecha' => now()->format('Y-m-d'),
            'hora' => '10:00:00',
            'sync_calendar' => false,
        ]);

        $this->assertNull($nota->google_event_id);
    }

    public function test_updating_a_synced_note_updates_google_calendar()
    {
        // First we create the note, bypassing the original observer to simulate an existing one
        $nota = new Nota([
            'user_id' => User::factory()->create()->id,
            'comentario' => 'Nota existente',
            'fecha' => now()->format('Y-m-d'),
            'hora' => '10:00:00',
            'sync_calendar' => true,
        ]);
        $nota->google_event_id = 'existing-mock-id';
        $nota->saveQuietly();

        $this->mock(GoogleCalendarService::class, function (MockInterface $mock) use ($nota) {
            $mock->shouldReceive('updateEvent')
                 ->once()
                 ->with(\Mockery::on(function ($arg) use ($nota) {
                     return $arg->id === $nota->id;
                 }));
        });

        $nota->comentario = 'Comentario actualizado';
        $nota->save();
    }

    public function test_deleting_a_synced_note_deletes_from_google_calendar()
    {
        $nota = new Nota([
            'user_id' => User::factory()->create()->id,
            'comentario' => 'Nota a borrar',
            'fecha' => now()->format('Y-m-d'),
            'hora' => '10:00:00',
            'sync_calendar' => true,
        ]);
        $nota->google_event_id = 'existing-mock-id';
        $nota->saveQuietly();

        $this->mock(GoogleCalendarService::class, function (MockInterface $mock) {
            $mock->shouldReceive('deleteEvent')
                 ->once()
                 ->with('existing-mock-id');
        });

        $nota->delete();
    }
}
