<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\Afp;

class AfpSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'PORVENIR', 'code' => '230301'],
            ['name' => 'COLPENSIONES', 'code' => '25-14'],
            ['name' => 'PROTECCIÓN', 'code' => '230201'],
            ['name' => 'COLFONDOS', 'code' => '231001'],
            ['name' => 'OLD MUTUAL', 'code' => '230901'],
        ];

        foreach ($items as $item) {
            Afp::updateOrCreate(
                ['name' => $item['name']],
                ['code' => $item['code'], 'is_active' => true]
            );
        }
    }
}
