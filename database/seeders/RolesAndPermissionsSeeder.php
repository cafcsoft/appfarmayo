<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permBi = \Spatie\Permission\Models\Permission::findOrCreate('access bi', 'web');
        $permAcc = \Spatie\Permission\Models\Permission::findOrCreate('access accounting', 'web');
        $permUsers = \Spatie\Permission\Models\Permission::findOrCreate('manage users', 'web');

        // Create roles
        $adminRole = Role::findOrCreate('Administrador', 'web');
        $contadorRole = Role::findOrCreate('contador', 'web');
        $developerRole = Role::findOrCreate('Developer', 'web');

        // Sync permissions to roles
        $adminRole->syncPermissions([$permBi, $permAcc, $permUsers]);
        $developerRole->syncPermissions([$permBi, $permAcc, $permUsers]);
        $contadorRole->syncPermissions([$permAcc]);

        // Assign to demo/existing users if present
        $admins = User::whereIn('username', ['admin', 'carlosf'])
            ->orWhere('email', 'admin@example.com')
            ->get();
        foreach ($admins as $admin) {
            $admin->syncRoles([$adminRole]);
        }

        // Create a base accountant user for demo/testing if not exists
        $contadorUser = User::updateOrCreate(
            ['email' => 'contador@example.com'],
            [
                'name' => 'Contador General',
                'username' => 'contador',
                'ruc_cedula' => '9999999',
                'recovery_pin' => '123456',
                'password' => Hash::make('password'),
            ]
        );
        $contadorUser->syncRoles([$contadorRole]);
    }
}
