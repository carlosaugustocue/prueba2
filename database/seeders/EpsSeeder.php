<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Patients\Models\Eps;

class EpsSeeder extends Seeder
{
    public function run(): void
    {
        $epsList = [
            ['name' => 'Nueva EPS', 'code' => 'EPS037'],
            ['name' => 'EPS Sanitas', 'code' => 'EPS005'],
            ['name' => 'EPS Sura', 'code' => 'EPS010'],
            ['name' => 'Salud Total EPS', 'code' => 'EPS002'],
            ['name' => 'Coomeva EPS', 'code' => 'EPS016'],
            ['name' => 'Compensar EPS', 'code' => 'EPS008'],
            ['name' => 'Famisanar EPS', 'code' => 'EPS017'],
            ['name' => 'Mutual Ser EPS', 'code' => 'EPS042'],
            ['name' => 'Coosalud EPS', 'code' => 'ESS024'],
            ['name' => 'Capital Salud EPS', 'code' => 'EPS055'],
            ['name' => 'Aliansalud EPS', 'code' => 'EPS001'],
            ['name' => 'Medimás EPS', 'code' => 'EPS041'],
        ];

        foreach ($epsList as $eps) {
            Eps::updateOrCreate(['code' => $eps['code']], ['name' => $eps['name'], 'is_active' => true]);
        }
    }
}
