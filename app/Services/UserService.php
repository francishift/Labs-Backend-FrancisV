<?php

namespace App\Services;

use App\Models\User;
use App\Models\VpnAccessLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    private VpnService $vpnService;

    public function __construct(VpnService $vpnService)
    {
        $this->vpnService = $vpnService;
    }

    public function crearUsuarioConVpn(array $datosValidados, int $adminId, string $ipAddress, string $userAgent): User
    {
        return DB::transaction(function () use ($datosValidados, $adminId, $ipAddress, $userAgent) {
            $plainPassword = $datosValidados['password'];

            $user = User::create([
                'name' => $datosValidados['name'],
                'email' => $datosValidados['email'],
                'password' => $plainPassword,
            ]);

            $user->syncRoles([$datosValidados['role']]);

            $vpnConfig = null;

            try {
                $keys = $this->vpnService->generateKeyPair();
                $internalIp = $this->vpnService->getNextAvailableIp();

                $device = $user->vpnDevices()->create([
                    'name' => 'Dispositivo Principal',
                    'public_key' => $keys['public'],
                    'internal_ip' => $internalIp,
                ]);

                $this->vpnService->addPeer($device);
                $vpnConfig = $this->vpnService->generateConfig($device, $keys['private']);

                VpnAccessLog::create([
                    'user_id' => $adminId,
                    'target_device_id' => $device->id,
                    'action' => 'CREATE_SUCCESS',
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'details' => "Dispositivo '{$device->name}' autogenerado en alta de usuario.",
                ]);
            } catch (\Throwable $e) {
                Log::error("Fallo al crear VPN en alta de usuario: " . $e->getMessage());
            }

            $user->notify(new \App\Notifications\WelcomeUserSpanish($plainPassword, $vpnConfig));

            return $user;
        });
    }

    public function actualizarUsuario(User $user, array $datosValidados, int $adminId): void
    {
        DB::transaction(function () use ($user, $datosValidados, $adminId) {
            $user->update([
                'name' => $datosValidados['name'],
                'email' => $datosValidados['email'],
            ]);

            if (!empty($datosValidados['password'])) {
                $user->password = $datosValidados['password'];
                $user->save();
            }

            if ($adminId !== $user->id) {
                $user->syncRoles([$datosValidados['role']]);
            }
        });
    }

    public function actualizarRol(User $user, string $roleName, int $adminId): void
    {
        if ($adminId === $user->id) {
            throw new \Exception('No puedes cambiar tu propio rol.');
        }

        $user->syncRoles([$roleName]);
    }
}
