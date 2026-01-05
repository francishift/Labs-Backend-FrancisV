<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = ['admin', 'coordinador', 'cliente', 'subcliente'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $adminEmail = 'correo@TU_DOMINIO';

        $user = User::where('email', $adminEmail)->first();

        if ($user) {
            $user->syncRoles(['admin']);
        }
    }
}
