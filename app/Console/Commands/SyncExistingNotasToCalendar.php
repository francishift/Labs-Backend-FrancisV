<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncExistingNotasToCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notas:sync-calendar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza todas las notas existentes (pendientes) con Google Calendar si no tienen ya un ID.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\GoogleCalendarService $calendarService)
    {
        $notas = \App\Models\Nota::where('sync_calendar', true)
            ->whereNull('google_event_id')
            ->get();

        if ($notas->isEmpty()) {
            $this->info('No hay notas pendientes de sincronizar.');
            return 0;
        }

        $this->info("Encontradas {$notas->count()} notas para sincronizar. Iniciando...");

        $bar = $this->output->createProgressBar(count($notas));
        $bar->start();

        foreach ($notas as $nota) {
            $eventId = $calendarService->createEvent($nota);
            if ($eventId) {
                $nota->google_event_id = $eventId;
                $nota->saveQuietly();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n¡Sincronización completada!");

        return 0;
    }
}
