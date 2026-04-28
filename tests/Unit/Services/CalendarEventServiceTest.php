<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\CalendarEvent;
use App\Services\CalendarEventService;
use App\Services\GoogleCalendarApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Carbon\Carbon;
use Google\Service\Calendar\Event as GoogleServiceEvent;
use Google\Service\Calendar\EventDateTime as GoogleEventDateTime;
use Google\Service\Calendar\Events as GoogleEventsList;

class CalendarEventServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalendarEventService $calendarService;
    private $googleApiMock;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->googleApiMock = Mockery::mock(GoogleCalendarApiService::class);
        $this->calendarService = new CalendarEventService($this->googleApiMock);
    }

    public function test_crear_evento_guarda_local_y_llama_a_google()
    {
        $datos = [
            'name' => 'Reunión de Prueba',
            'start_date' => '2024-06-01 10:00:00',
            'end_date' => '2024-06-01 11:00:00',
            'reminders' => [
                ['minutes' => 15]
            ]
        ];

        $mockGoogleEvent = new GoogleServiceEvent();
        $mockGoogleEvent->setId('google_id_123');

        $this->googleApiMock->shouldReceive('insertEvent')
            ->once()
            ->andReturn($mockGoogleEvent);

        $evento = $this->calendarService->crearEventoLocalYGoogle($datos, $this->user->id);

        $this->assertDatabaseHas('calendar_events', [
            'id' => $evento->id,
            'name' => 'Reunión de Prueba',
            'google_event_id' => 'google_id_123',
            'user_id' => $this->user->id,
        ]);
        
        $this->assertIsArray($evento->reminders);
        $this->assertEquals(15, $evento->reminders[0]['minutes']);
        $this->assertFalse($evento->reminders[0]['notified']);
    }

    public function test_combinar_eventos_une_correctamente_sin_duplicados()
    {
        // 1. Crear evento local que supuestamente ya está sincronizado
        $eventoLocal = CalendarEvent::create([
            'name' => 'Evento Local Sincronizado',
            'start_date' => '2024-06-01 10:00:00',
            'end_date' => '2024-06-01 11:00:00',
            'google_event_id' => 'google_id_existente',
            'user_id' => $this->user->id,
            'reminders' => []
        ]);

        // 2. Preparar respuesta de Google con ese evento y uno externo
        $eventoG1 = new GoogleServiceEvent();
        $eventoG1->setId('google_id_existente');
        $eventoG1->setSummary('Nombre desde Google'); // El local debería prevalecer
        $start1 = new GoogleEventDateTime();
        $start1->setDateTime(Carbon::parse('2024-06-01 10:00:00')->format('c'));
        $eventoG1->setStart($start1);
        $end1 = new GoogleEventDateTime();
        $end1->setDateTime(Carbon::parse('2024-06-01 11:00:00')->format('c'));
        $eventoG1->setEnd($end1);

        $eventoG2 = new GoogleServiceEvent();
        $eventoG2->setId('google_id_externo');
        $eventoG2->setSummary('Evento Externo');
        $start2 = new GoogleEventDateTime();
        $start2->setDateTime(Carbon::parse('2024-06-02 10:00:00')->format('c'));
        $eventoG2->setStart($start2);
        $end2 = new GoogleEventDateTime();
        $end2->setDateTime(Carbon::parse('2024-06-02 11:00:00')->format('c'));
        $eventoG2->setEnd($end2);

        $mockEventList = new GoogleEventsList();
        $mockEventList->setItems([$eventoG1, $eventoG2]);

        $this->googleApiMock->shouldReceive('listEvents')
            ->once()
            ->andReturn($mockEventList);

        // 3. Ejecutar combinación
        $combinados = $this->calendarService->obtenerEventosCombinados(null, null, $this->user->id);

        // 4. Validar
        $this->assertCount(2, $combinados);
        
        // Verificar que el primero prevalece la info local
        $this->assertEquals('Evento Local Sincronizado', $combinados[0]['title']);
        $this->assertFalse($combinados[0]['extendedProps']['is_external']);
        
        // Verificar que el segundo se marca como externo
        $this->assertEquals('Evento Externo', $combinados[1]['title']);
        $this->assertTrue($combinados[1]['extendedProps']['is_external']);
        $this->assertEquals('#27272a', $combinados[1]['backgroundColor']);
    }
}
