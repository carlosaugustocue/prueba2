<?php

namespace Database\Seeders;

use App\Modules\PilaManagement\Models\PilaRiskClass;
use Illuminate\Database\Seeder;

class PilaRiskClassSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $row) {
            PilaRiskClass::updateOrCreate(
                ['level' => $row['level']],
                [
                    'class_name' => $row['class_name'],
                    'description' => $row['description'],
                    'rate' => $row['rate'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function rows(): array
    {
        return [
            ['level' => 0, 'class_name' => null, 'description' => 'No aplica', 'rate' => 0.00000],
            ['level' => 1, 'class_name' => 'I', 'description' => 'Riesgo mínimo', 'rate' => 0.00522],
            ['level' => 2, 'class_name' => 'II', 'description' => 'Riesgo bajo', 'rate' => 0.01044],
            ['level' => 3, 'class_name' => 'III', 'description' => 'Riesgo medio', 'rate' => 0.02436],
            ['level' => 4, 'class_name' => 'IV', 'description' => 'Riesgo alto', 'rate' => 0.04350],
            ['level' => 5, 'class_name' => 'V', 'description' => 'Riesgo máximo', 'rate' => 0.06960],
        ];
    }
}
