<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $supervisorRole = Role::where('name', 'supervisor')->first();
        $agentRole = Role::where('name', 'agent')->first();

        User::updateOrCreate(
            ['email' => 'admin@gruposerviconli.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'supervisor@gruposerviconli.com'],
            [
                'name' => 'Supervisor Central',
                'password' => Hash::make('password'),
                'role_id' => $supervisorRole->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'biviana@gruposerviconli.com'],
            [
                'name' => 'Biviana',
                'password' => Hash::make('password'),
                'role_id' => $agentRole->id,
                'is_active' => true,
            ]
        );
    }
}
