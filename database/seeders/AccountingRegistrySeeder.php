<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\AccountingRegistry;

class AccountingRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['RECIBO_CAJA', 'RECIBO_CAJA'],
            ['FACTURA_ELECTRONICA', 'FACTURA_ELECTRONICA'],
        ];

        foreach ($rows as [$name, $code]) {
            AccountingRegistry::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
