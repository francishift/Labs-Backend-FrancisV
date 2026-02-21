<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::query()
            ->orderBy('name')
            ->pluck('name')
            ->values();

        $users = User::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->with(['roles:id,name', 'vpnDevices'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->paginate(10)
            ->through(function (User $u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'roles' => $u->roles->pluck('name')->values(),
                    'vpn_devices' => $u->vpnDevices,
                    'created_at' => optional($u->created_at)->toDateTimeString(),
                ];
            })
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'me' => [
                'id' => $request->user()->id,
                'email' => $request->user()->email,
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $roleNames = Role::query()->pluck('name')->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in($roleNames)],
            'password' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        $plainPassword = $data['password'];

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $plainPassword,
        ]);

        $user->syncRoles([$data['role']]);

        try {
            $vpnService = app(\App\Services\VpnService::class);
            $keys = $vpnService->generateKeyPair();
            $internalIp = $vpnService->getNextAvailableIp();

            $device = $user->vpnDevices()->create([
                'name' => 'Dispositivo Principal',
                'public_key' => $keys['public'],
                'internal_ip' => $internalIp,
            ]);

            $vpnService->addPeer($device);
            $vpnConfig = $vpnService->generateConfig($device, $keys['private']);

            \App\Models\VpnAccessLog::create([
                'user_id' => request()->user()->id,
                'target_device_id' => $device->id,
                'action' => 'CREATE_SUCCESS',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => "Dispositivo '{$device->name}' autogenerado en alta de usuario.",
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Fallo al crear VPN en alta de usuario: " . $e->getMessage());
            $vpnConfig = null;
        }

        $user->notify(new \App\Notifications\WelcomeUserSpanish($plainPassword, $vpnConfig));

        return back()
            ->with('success', 'Usuario creado y email enviado.');
    }

    public function updateRole(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors([
                'role' => 'No puedes cambiar tu propio rol.',
            ]);
        }

        $roleNames = Role::query()->pluck('name')->all();

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in($roleNames)],
        ]);

        $user->syncRoles([$data['role']]);

        return back()
            ->with('success', 'Rol actualizado para ' . $user->email);
    }

    public function update(Request $request, User $user)
    {
        $roleNames = Role::query()->pluck('name')->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in($roleNames)],
            'password' => ['nullable', 'string', 'min:10', 'max:255'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (!empty($data['password'])) {
            $user->password = $data['password'];
            $user->save();
        }

        if ($request->user()->id !== $user->id) {
            $user->syncRoles([$data['role']]);
        }

        return back()
            ->with('success', 'Usuario actualizado: ' . $user->email);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return redirect()
                ->route('admin.usuarios.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return back()
            ->with('success', 'Usuario eliminado: ' . $user->email);
    }
}