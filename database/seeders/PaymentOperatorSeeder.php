<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\PaymentOperator;

class PaymentOperatorSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'ENLACE OPERATIVO', 'code' => null],
            ['name' => 'SIMPLE', 'code' => null],
            ['name' => 'ASOPAGOS', 'code' => null],
            ['name' => 'APORTES EN LINEA', 'code' => null],
            ['name' => 'SOI', 'code' => null],
            ['name' => 'MI PLANILLA', 'code' => null],
        ];

        foreach ($items as $item) {
            PaymentOperator::updateOrCreate(
                ['name' => $item['name']],
                ['code' => $item['code'], 'is_active' => true]
            );
        }
    }
}
