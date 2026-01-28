<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Auth\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'Acceso total al sistema',
                'permissions' => ['*'],
            ],
            [
                'name' => 'supervisor',
                'display_name' => 'Supervisor',
                'description' => 'Puede ver reportes, historial y gestionar citas',
                'permissions' => ['appointments.*', 'patients.*', 'reports.*'],
            ],
            [
                'name' => 'agent',
                'display_name' => 'Agente',
                'description' => 'Puede gestionar citas y pacientes',
                'permissions' => ['appointments.view', 'appointments.create', 'appointments.update', 'patients.*'],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
