<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\NoveltyType;

class NoveltyTypeSeeder extends Seeder
{
    /**
     * Tipos de novedad PILA. Ref: mapeo_datasegura_a_base_de_datos.md (Col X/Y)
     */
    public function run(): void
    {
        $rows = [
            ['Ingreso', 'ENTRY'],
            ['Retiro', 'WITHDRAWAL'],
            ['Traslado EPS / EOC', 'EPS_CHANGE'],
        ];

        foreach ($rows as [$name, $code]) {
            NoveltyType::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
