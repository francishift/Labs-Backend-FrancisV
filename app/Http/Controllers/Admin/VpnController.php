<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VpnDevice;
use App\Services\VpnService;
use Illuminate\Support\Facades\Log;

class VpnController extends Controller
{
    protected VpnService $vpnService;

    public function __construct(VpnService $vpnService)
    {
        $this->vpnService = $vpnService;
    }

    public function store(Request $request, User $user)
    {
        $adminId = auth()->id();
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $keys = $this->vpnService->generateKeyPair();
            $internalIp = $this->vpnService->getNextAvailableIp();

            $device = $user->vpnDevices()->create([
                'name' => $request->name,
                'public_key' => $keys['public'],
                'internal_ip' => $internalIp,
            ]);

            // Añadir al servidor Wireguard
            $this->vpnService->addPeer($device);

            // Generar config solo esta vez para el QR
            $config = $this->vpnService->generateConfig($device, $keys['private']);

            \App\Models\VpnAccessLog::create([
                'user_id' => $adminId,
                'target_device_id' => $device->id,
                'action' => 'CREATE_SUCCESS',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => "Dispositivo '{$device->name}' creado e IP {$internalIp} asignada.",
            ]);

            return back()
                ->with('success', 'Dispositivo VPN añadido correctamente.')
                ->with('vpn_config', $config);

        } catch (\Throwable $e) {
            Log::error("CRITICAL: Fallo al crear dispositivo VPN para User {$user->id}: " . $e->getMessage());

             \App\Models\VpnAccessLog::create([
                'user_id' => $adminId,
                'target_device_id' => null,
                'action' => 'CREATE_FAIL',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error crítico al generar dispositivo VPN. Revise log de sistema.');
        }
    }

    public function destroy($id)
    {
        $adminId = auth()->id();
        Log::info("DEBUG: Intentando borrado DIRECTO de VPN ID: {$id} por Admin ID: {$adminId}");

        try {
            // 1. Audit Log: Intento
            \App\Models\VpnAccessLog::create([
                'user_id' => $adminId,
                'target_device_id' => $id,
                'action' => 'DELETE_ATTEMPT',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => "Iniciando borrado de dispositivo ID {$id}",
            ]);

            // 2. Recuperamos datos necesarios para Wireguard antes de borrar
            $deviceData = \App\Models\VpnDevice::where('id', $id)->first();

            // 3. Usamos borrado directo en DB
            \App\Models\VpnDevice::where('id', $id)->delete();

            // 4. Audit Log: Éxito DB
             \App\Models\VpnAccessLog::create([
                'user_id' => $adminId,
                'target_device_id' => $id, // ID preservado aunque se borre
                'action' => 'DELETE_SUCCESS_DB',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => "Borrado soft-delete completado.",
            ]);

            // 5. Intentar borrar de Wireguard (Best Effort)
            if ($deviceData) {
                try {
                    $this->vpnService->removePeer($deviceData);
                    
                    \App\Models\VpnAccessLog::create([
                        'user_id' => $adminId,
                        'target_device_id' => $id,
                        'action' => 'WG_REMOVE_SUCCESS',
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'details' => "Peer eliminado de Wireguard correctamente.",
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Error no bloqueante al borrar peer WG: " . $e->getMessage());
                    
                    \App\Models\VpnAccessLog::create([
                        'user_id' => $adminId,
                        'target_device_id' => $id,
                        'action' => 'WG_REMOVE_FAIL',
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'details' => "Fallo al borrar peer WG: " . $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error("CRITICAL: Error al borrar dispositivo VPN {$id}: " . $e->getMessage());

            \App\Models\VpnAccessLog::create([
                'user_id' => $adminId,
                'target_device_id' => $id,
                'action' => 'DELETE_CRITICAL_FAIL',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => $e->getMessage(),
            ]);
            
            // Aunque falle, para el usuario "Safe Delete" puede ser mejor decir que contacte soporte
            // o intentar forzar borrado. De momento mantenemos return back con aviso.
            return back()->with('error', 'Error al borrar el dispositivo. Se ha registrado el incidente.');
        }

        return back()->with('success', 'Acceso VPN revocado correctamente.');
    }

    public function destroyTest($id)
    {
        return back()->with('success', 'Prueba de borrado (sin efecto) completada.');
    }
}
