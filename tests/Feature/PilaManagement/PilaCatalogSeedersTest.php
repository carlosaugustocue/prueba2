<?php

namespace Tests\Feature\PilaManagement;

use Database\Seeders\PilaCotizanteTypeSeeder;
use Database\Seeders\PilaRiskClassSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilaCatalogSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_pila_cotizante_type_seeder_loads_expected_catalog(): void
    {
        $this->seed(PilaCotizanteTypeSeeder::class);

        $this->assertDatabaseCount('pila_cotizante_types', 19);
        $this->assertDatabaseHas('pila_cotizante_types', [
            'code' => '01',
            'name' => 'Dependiente',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('pila_cotizante_types', [
            'code' => '59',
            'name' => 'Independiente con contrato de prestación de servicios superior a 1 mes',
            'is_active' => true,
        ]);
    }

    public function test_pila_risk_class_seeder_loads_expected_catalog(): void
    {
        $this->seed(PilaRiskClassSeeder::class);

        $this->assertDatabaseCount('pila_risk_classes', 6);
        $this->assertDatabaseHas('pila_risk_classes', [
            'level' => 0,
            'class_name' => null,
            'description' => 'No aplica',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('pila_risk_classes', [
            'level' => 5,
            'class_name' => 'V',
            'description' => 'Riesgo máximo',
            'rate' => 0.06960,
            'is_active' => true,
        ]);
    }
}
