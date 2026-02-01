<?php

namespace App\Services;

use App\Models\VpnDevice;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class VpnService
{
    protected string $interface = 'wg0';

    /**
     * Genera un par de llaves (privada y pública) de Wireguard.
     */
    public function generateKeyPair(): array
    {
        $privateKey = trim(shell_exec('wg genkey'));
        $publicKey = trim(shell_exec("echo '$privateKey' | wg pubkey"));

        return [
            'private' => $privateKey,
            'public' => $publicKey,
        ];
    }

    /**
     * Obtiene la siguiente IP disponible en el rango 10.0.0.x.
     */
    /**
     * Obtiene la siguiente IP disponible en el rango 10.0.0.x.
     */
    public function getNextAvailableIp(): string
    {
        // 1. Obtener IPs de dispositivos activos
        $activeIps = VpnDevice::pluck('internal_ip')->toArray();

        // 2. Obtener IPs de dispositivos eliminados (soft deleted)
        // Esto evita hacer un query por cada iteración del loop (N+1 fix)
        $trashedDevices = VpnDevice::onlyTrashed()->get(['id', 'internal_ip']);
        $trashedIps = $trashedDevices->pluck('internal_ip')->toArray();
        
        // El servidor suele ser .1, empezamos en .2
        for ($i = 2; $i < 255; $i++) {
            $ip = "10.0.0.$i";
            
            if (!in_array($ip, $activeIps)) {
                // Si la IP está en un registro borrado, lo eliminamos permanentemente para liberar la IP
                if (in_array($ip, $trashedIps)) {
                     // Podría haber múltiples, borramos todos (aunque debería ser único por unique index si existiera)
                     $collisions = $trashedDevices->where('internal_ip', $ip);
                     foreach ($collisions as $collision) {
                         $collision->forceDelete();
                     }
                }

                return $ip;
            }
        }

        throw new \Exception("No hay IPs disponibles en el rango VPN.");
    }

    /**
     * Registra un peer en el servidor Wireguard en tiempo real.
     */
    public function addPeer(VpnDevice $device): bool
    {
        $command = "sudo /usr/bin/wg set {$this->interface} peer {$device->public_key} allowed-ips {$device->internal_ip}/32";
        
        $result = Process::run($command);

        if ($result->failed()) {
            Log::error("Error al añadir peer VPN: " . $result->errorOutput());
            return false;
        }

        return true;
    }

    /**
     * Elimina un peer del servidor Wireguard.
     */
    public function removePeer(VpnDevice $device): bool
    {
        try {
            $command = "sudo /usr/bin/wg set {$this->interface} peer {$device->public_key} remove";
            
            $result = Process::run($command);

            if ($result->failed()) {
                Log::error("Error al eliminar peer VPN (Comando falló): " . $result->errorOutput());
                // Retornamos true para no impedir el borrado en DB, aunque falle WG
                return true; 
            }
        } catch (\Throwable $e) {
            Log::error("Excepción al ejecutar comando WG: " . $e->getMessage());
            return true; // Fail-safe: permitir borrado en DB
        }

        return true;
    }

    /**
     * Genera el archivo de configuración para el cliente.
     */
    public function generateConfig(VpnDevice $device, string $privateKey): string
    {
        $serverPublicKey = trim(shell_exec("sudo /usr/bin/wg show {$this->interface} public-key"));
        $endpoint = config('services.vpn.endpoint');

        return <<<CONFIG
[Interface]
PrivateKey = {$privateKey}
Address = {$device->internal_ip}/24
DNS = 10.0.0.1

[Peer]
PublicKey = {$serverPublicKey}
Endpoint = {$endpoint}
AllowedIPs = 10.0.0.0/24
PersistentKeepalive = 25
CONFIG;
    }
}
