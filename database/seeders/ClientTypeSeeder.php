<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\ClientType;

class ClientTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'SERVICONLI', 'code' => 'SERVICONLI'],
            ['name' => 'INDEPENDIENTE', 'code' => 'INDEPENDENT'],
            ['name' => 'DEPENDIENTE', 'code' => 'DEPENDENT'],
            ['name' => 'COLOMBIANO RESIDENTE EN EL EXTERIOR', 'code' => 'FOREIGN_RESIDENT'],
        ];

        foreach ($rows as $row) {
            ClientType::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'is_active' => true]
            );
        }
    }
}
