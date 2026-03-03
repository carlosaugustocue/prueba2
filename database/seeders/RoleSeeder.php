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
                'description' => 'Puede ver reportes, historial y gestionar citas y afiliados',
                'permissions' => ['appointments.*', 'affiliates.*', 'reports.*'],
            ],
            [
                'name' => 'agent',
                'display_name' => 'Agente',
                'description' => 'Puede gestionar citas y afiliados',
                'permissions' => ['appointments.view', 'appointments.create', 'appointments.update', 'affiliates.*'],
            ],
            [
                'name' => 'atencion',
                'display_name' => 'Atención / Citas',
                'description' => 'Solo módulo de solicitudes y citas (y consulta de afiliados para vincular)',
                'permissions' => ['appointments.*', 'affiliates.view'],
            ],
            [
                'name' => 'seguridad_social',
                'display_name' => 'Seguridad Social',
                'description' => 'Solo módulo de afiliados y seguridad social (sin citas)',
                'permissions' => ['affiliates.*'],
            ],
            [
                'name' => 'cartera',
                'display_name' => 'Cartera',
                'description' => 'Gestión de cartera y control de planillas (afiliados, pagadores y planillas, sin módulo de citas)',
                'permissions' => ['affiliates.*', 'payers.*', 'payrolls.*', 'affiliate_tasks.*'],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
