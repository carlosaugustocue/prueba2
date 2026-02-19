<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\Ccf;

class CcfSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'COMFENALCO QUINDÍO', 'code' => 'CCF43'],
            ['name' => 'COMFAMILIAR RISARALDA', 'code' => 'CCF44'],
            ['name' => 'COMFANDI', 'code' => 'CCF57'],
            ['name' => 'COMFAMILIAR CALDAS', 'code' => 'CCF11'],
            ['name' => 'COLSUBSIDIO', 'code' => 'CCF22'],
            ['name' => 'COMPENSAR', 'code' => 'EPS008'],
        ];

        foreach ($items as $item) {
            Ccf::updateOrCreate(
                ['name' => $item['name']],
                ['code' => $item['code'], 'is_active' => true]
            );
        }
    }
}
