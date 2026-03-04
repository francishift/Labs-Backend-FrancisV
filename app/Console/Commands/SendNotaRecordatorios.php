<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Nota;
use Carbon\Carbon;
use App\Notifications\NotaRecordatorio;

class SendNotaRecordatorios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notas:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía notificaciones push para las notas pendientes según su fecha, hora y minutos de antelación configurados.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Obtener solo notas no notificadas y con usuarios existentes
        $notas = Nota::with('user')
            ->where('notificado', false)
            ->whereHas('user')
            ->get();

        $count = 0;

        foreach ($notas as $nota) {
            // Si la nota está configurada para no enviar notificación
            if ($nota->notificacion_minutos_antes == -1) {
                $nota->update(['notificado' => true]); // La marcamos para no procesarla más en el futuro
                continue;
            }

            // Reconstruir la fecha y hora completa
            $notaDateTime = Carbon::parse($nota->fecha->format('Y-m-d') . ' ' . $nota->hora);
            
            // Restar los minutos de antelación
            $triggerTime = $notaDateTime->subMinutes($nota->notificacion_minutos_antes);

            // Si el momento de disparar ya pasó o es ahora, enviamos la notificación
            if ($now->greaterThanOrEqualTo($triggerTime)) {
                $nota->user->notify(new NotaRecordatorio($nota));

                // Marcar como notificado
                $nota->update(['notificado' => true]);
                $count++;
            }
        }

        $this->info("Se enviaron {$count} recordatorios de notas.");
    }
}
