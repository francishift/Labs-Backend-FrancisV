<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use App\Models\VpnDevice;
use Carbon\Carbon;

class SyncVpnHandshakes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpn:sync-handshakes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs the last handshake time from Wireguard to the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ejecutar comando wg show dump
        // Formato dump: public_key preshared_key endpoint allowed_ips latest_handshake transfer_rx transfer_tx persistent_keepalive
        $process = Process::run('sudo /usr/bin/wg show wg0 dump');

        if ($process->failed()) {
            $this->error('Failed to run wg command: ' . $process->errorOutput());
            return 1;
        }

        $lines = explode("\n", trim($process->output()));
        $count = 0;

        foreach ($lines as $line) {
            $parts = explode("\t", $line);
            
            // La primera línea suele ser la del servidor (interface), la ignoramos si no tiene handshake o si es la propia int
            if (count($parts) < 5) continue;

            $publicKey = $parts[0];
            $handshakeTimestamp = (int) $parts[4];

            // Si nunca hubo handshake es 0. Solo actualizamos si hay actividad.
            if ($handshakeTimestamp > 0) {
                // Convertir timestamp a Y-m-d H:i:s
                $lastHandshake = Carbon::createFromTimestamp($handshakeTimestamp);

                // Buscar dispositivo y actualizar
                // Podríamos optimizar con upsert o transacciones si fueran muchos, 
                // pero por ahora 1 a 1 está bien para < 100 usuarios.
                $device = VpnDevice::where('public_key', $publicKey)->first();

                if ($device) {
                    // Solo actualizar si la fecha es más reciente de lo que tenemos
                    if (!$device->last_handshake_at || $device->last_handshake_at->lt($lastHandshake)) {
                        $device->update(['last_handshake_at' => $lastHandshake]);
                        $count++;
                    }
                }
            }
        }

        $this->info("Synced {$count} active connections.");
    }
}
