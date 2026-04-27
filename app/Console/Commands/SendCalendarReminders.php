<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use App\Notifications\CalendarReminder;

class SendCalendarReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía notificaciones push para los eventos pendientes según sus arrays JSON de recordatorios configurados.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Obtener eventos con recordatorios y que tengan usuario
        $events = CalendarEvent::with('user')
            ->whereNotNull('reminders')
            ->whereHas('user')
            ->get();

        $count = 0;

        foreach ($events as $event) {
            $reminders = $event->reminders;
            $updated = false;
            $shouldNotify = false;

            if (!is_array($reminders)) continue;

            foreach ($reminders as $key => $rem) {
                if (isset($rem['notified']) && $rem['notified'] === true) {
                    continue;
                }

                // Restar los minutos de antelación
                $triggerTime = Carbon::parse($event->start_date)->subMinutes((int)$rem['minutes']);

                // Si el momento de disparar ya pasó o es ahora, enviamos la notificación
                if ($now->greaterThanOrEqualTo($triggerTime)) {
                    $shouldNotify = true;
                    $reminders[$key]['notified'] = true;
                    $updated = true;
                }
            }

            if ($shouldNotify) {
                $event->user->notify(new CalendarReminder($event));
                $count++;
            }

            if ($updated) {
                // Actualizar DB con el flag 'notified: true' en los rems que dispararon
                $event->update(['reminders' => $reminders]);
            }
        }

        $this->info("Se enviaron {$count} recordatorios de calendario.");
    }
}
