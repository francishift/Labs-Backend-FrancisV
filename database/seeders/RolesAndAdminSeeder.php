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

        $adminEmail = config('app.admin_email', env('ADMIN_EMAIL', 'admin@example.com'));

        $user = User::where('email', $adminEmail)->first();

        if ($user) {
            $user->syncRoles(['admin']);
        }
    }
}
