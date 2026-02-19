<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\Arp;

class ArpSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'POSITIVA', 'code' => '14-23'],
            ['name' => 'SURA', 'code' => '14-11'],
            ['name' => 'COLMENA', 'code' => '14-25'],
            ['name' => 'COLPATRIA', 'code' => '14-4'],
        ];

        foreach ($items as $item) {
            Arp::updateOrCreate(
                ['name' => $item['name']],
                ['code' => $item['code'], 'is_active' => true]
            );
        }
    }
}
