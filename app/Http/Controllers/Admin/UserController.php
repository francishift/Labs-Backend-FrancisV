<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserService;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

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

    public function store(StoreUserRequest $request)
    {
        $this->userService->crearUsuarioConVpn(
            $request->validated(),
            $request->user()->id,
            $request->ip(),
            $request->userAgent()
        );

        return back()->with('success', 'Usuario creado y email enviado.');
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'string', \Illuminate\Validation\Rule::in(Role::query()->pluck('name')->all())],
        ]);

        try {
            $this->userService->actualizarRol($user, $request->input('role'), $request->user()->id);
            return back()->with('success', 'Rol actualizado para ' . $user->email);
        } catch (\Exception $e) {
            return back()->withErrors(['role' => $e->getMessage()]);
        }
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->actualizarUsuario(
            $user,
            $request->validated(),
            $request->user()->id
        );

        return back()->with('success', 'Usuario actualizado: ' . $user->email);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return redirect()
                ->route('admin.usuarios.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado: ' . $user->email);
    }
}