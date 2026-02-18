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
                'permissions' => ['appointments.*', 'affiliates.*', 'reports.*'],
            ],
            [
                'name' => 'agent',
                'display_name' => 'Agente',
                'description' => 'Puede gestionar citas y afiliados',
                'permissions' => ['appointments.view', 'appointments.create', 'appointments.update', 'affiliates.*'],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
