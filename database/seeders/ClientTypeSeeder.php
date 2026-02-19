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
            ['name' => 'INDEPENDENT', 'code' => 'INDEPENDENT'],
            ['name' => 'DEPENDENT', 'code' => 'DEPENDENT'],
            ['name' => 'FOREIGN_RESIDENT', 'code' => 'FOREIGN_RESIDENT'],
        ];

        foreach ($rows as $row) {
            ClientType::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'is_active' => true]
            );
        }
    }
}
