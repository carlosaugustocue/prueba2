<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\NoveltyType;

class NoveltyTypeSeeder extends Seeder
{
    /**
     * Tipos de novedad PILA.
     * TAE (traslado a otra EPS) y TDE (traslado desde otra EPS) se registran por separado
     * para no perder la dirección del traslado.
     * Ref: mapeo_datasegura_a_base_de_datos.md (Col X/Y), Resolución 2388/2016.
     */
    public function run(): void
    {
        foreach ($this->getRows() as $row) {
            NoveltyType::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'is_active' => true]
            );
        }
    }

    private function getRows(): array
    {
        return [
            ['code' => 'ING', 'name' => 'Ingreso'],
            ['code' => 'RET', 'name' => 'Retiro'],
            ['code' => 'TAE', 'name' => 'Traslado a otra EPS / EOC'],
            ['code' => 'TDE', 'name' => 'Traslado desde otra EPS / EOC'],
            ['code' => 'TAP', 'name' => 'Traslado a otra AFP'],
            ['code' => 'TDP', 'name' => 'Traslado desde otra AFP'],
            ['code' => 'VSP', 'name' => 'Variación permanente de salario'],
            ['code' => 'VST', 'name' => 'Variación transitoria de salario'],
            ['code' => 'SLN', 'name' => 'Suspensión temporal del contrato / licencia no remunerada'],
            ['code' => 'IGE', 'name' => 'Incapacidad por enfermedad general'],
            ['code' => 'LMA', 'name' => 'Licencia de maternidad / paternidad'],
            ['code' => 'VAC', 'name' => 'Vacaciones'],
            ['code' => 'AVP', 'name' => 'Aporte voluntario a pensiones'],
            ['code' => 'VCT', 'name' => 'Variación centros de trabajo'],
            ['code' => 'IRP', 'name' => 'Incapacidad por accidente de trabajo o enfermedad laboral'],
            ['code' => 'COR', 'name' => 'Corrección'],
        ];
    }
}
