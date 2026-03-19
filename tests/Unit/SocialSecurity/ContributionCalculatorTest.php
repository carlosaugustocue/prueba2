<?php

namespace Tests\Unit\SocialSecurity;

use App\Modules\SocialSecurity\Services\ContributionCalculator;
use App\Modules\SocialSecurity\Services\ContributorTypeRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verificación de liquidación con SMLMV 2026 ($1.750.905).
 * Casos de referencia: dependiente e independiente con IBC = 1 SMLMV.
 */
class ContributionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private const SMLMV_2026 = 1_750_905.0;

    protected function setUp(): void
    {
        parent::setUp();

        // Datos mínimos de contributor_types para que ContributorTypeRules funcione.
        DB::table('contributor_types')->updateOrInsert(
            ['code' => '01'],
            [
                'code' => '01',
                'name' => 'Dependiente',
                'is_dependent' => true,
                'parafiscales_allowed' => true,
                'health_applies' => true,
                'pension_applies' => true,
                'arl_applies' => true,
                'ccf_applies' => true,
                'is_proportional' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        DB::table('contributor_types')->updateOrInsert(
            ['code' => '02'],
            [
                'code' => '02',
                'name' => 'Servicio Doméstico',
                'is_dependent' => true,
                'parafiscales_allowed' => false,
                'health_applies' => true,
                'pension_applies' => true,
                'arl_applies' => true,
                'ccf_applies' => true,
                'is_proportional' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        DB::table('contributor_types')->updateOrInsert(
            ['code' => '03'],
            [
                'code' => '03',
                'name' => 'Independiente',
                'is_dependent' => false,
                'parafiscales_allowed' => false,
                'health_applies' => true,
                'pension_applies' => true,
                'arl_applies' => true,
                'ccf_applies' => false,
                'is_proportional' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        ContributorTypeRules::clearCache();
    }

    private function params2026(): array
    {
        return [
            'HEALTH' => ['TOTAL' => 12.5, 'EMPLOYER' => 8.5, 'EMPLOYEE' => 4.0, 'INDEPENDENT' => 12.5],
            'PENSION' => ['TOTAL' => 16.0, 'EMPLOYER' => 12.0, 'EMPLOYEE' => 4.0, 'INDEPENDENT' => 16.0],
            'ARL' => ['RISK_1' => 0.522, 'RISK_2' => 1.044, 'RISK_3' => 2.436, 'RISK_4' => 4.350, 'RISK_5' => 6.960],
            'CCF' => ['TOTAL' => 4.0],
            'SENA' => ['TOTAL' => 2.0],
            'ICBF' => ['TOTAL' => 3.0],
            'FSP' => [
                'THRESHOLD_4_16' => 1.0, 'THRESHOLD_16_17' => 1.2, 'THRESHOLD_17_18' => 1.4,
                'THRESHOLD_18_19' => 1.6, 'THRESHOLD_19_20' => 1.8, 'THRESHOLD_20_PLUS' => 2.0,
            ],
        ];
    }

    public function test_dependiente_ibc_1_smlmv_coincide_con_tabla_referencia(): void
    {
        $calculator = new ContributionCalculator;
        $params = $this->params2026();
        $periodDate = '2026-01-01';

        $b = $calculator->calculate(
            ibc: self::SMLMV_2026,
            arlRiskClass: 1,
            contributorCode: '01',
            hasParafiscales: true,
            params: $params,
            smlmv: self::SMLMV_2026,
            periodDate: $periodDate,
        );

        // Referencia: Empleador 8.5% = $148.827, Empleado 4% = $70.036 (tabla redondeada a pesos)
        $this->assertEqualsWithDelta(148_827, $b->healthEmployer, 1);
        $this->assertEqualsWithDelta(70_036, $b->healthEmployee, 1);
        $this->assertEqualsWithDelta(218_863, $b->healthTotal, 1);

        $this->assertEqualsWithDelta(210_109, $b->pensionEmployer, 1);
        $this->assertEqualsWithDelta(70_036, $b->pensionEmployee, 1);
        $this->assertEqualsWithDelta(280_145, $b->pensionTotal, 1);

        $this->assertEqualsWithDelta(9_140, $b->arlAmount, 1);
        $this->assertEqualsWithDelta(70_036, $b->ccfAmount, 1);
        $this->assertEqualsWithDelta(35_018, $b->senaAmount, 1);
        $this->assertEqualsWithDelta(52_527, $b->icbfAmount, 1);
        $this->assertEquals(0.0, $b->fspAmount, 'FSP no aplica con IBC < 4 SMLMV');

        $totalEmpleador = $b->healthEmployer + $b->pensionEmployer + $b->arlAmount + $b->ccfAmount + $b->senaAmount + $b->icbfAmount;
        $totalEmpleado = $b->healthEmployee + $b->pensionEmployee;
        $this->assertEqualsWithDelta(525_657, $totalEmpleador, 2, 'Total empleador debe ser ~525.657');
        $this->assertEqualsWithDelta(140_072, $totalEmpleado, 2, 'Total empleado debe ser ~140.072');
        $this->assertEqualsWithDelta(665_729, $b->totalAmount, 2, 'Total planilla PILA debe ser ~665.729');
    }

    public function test_independiente_ibc_1_smlmv_coincide_con_tabla_referencia(): void
    {
        $calculator = new ContributionCalculator;
        $params = $this->params2026();
        $periodDate = '2026-01-01';

        $b = $calculator->calculate(
            ibc: self::SMLMV_2026,
            arlRiskClass: 1,
            contributorCode: '03',
            hasParafiscales: false,
            params: $params,
            smlmv: self::SMLMV_2026,
            periodDate: $periodDate,
        );

        $this->assertEqualsWithDelta(218_863, $b->healthTotal, 1, 'Salud 12.5%');
        $this->assertEquals(0.0, $b->healthEmployer);
        $this->assertEqualsWithDelta(218_863, $b->healthEmployee, 1);

        $this->assertEqualsWithDelta(280_145, $b->pensionTotal, 1, 'Pensión 16%');
        $this->assertEquals(0.0, $b->pensionEmployer);
        $this->assertEqualsWithDelta(280_145, $b->pensionEmployee, 1);

        $this->assertEqualsWithDelta(9_140, $b->arlAmount, 1, 'ARL Riesgo I voluntaria');
        $this->assertEquals(0.0, $b->ccfAmount);
        $this->assertEquals(0.0, $b->parafiscalAmount);
        $this->assertEquals(0.0, $b->fspAmount);

        $this->assertEqualsWithDelta(508_148, $b->totalAmount, 2, 'Total independiente debe ser ~508.148');
    }

    public function test_dependiente_sin_parafiscales_no_liquida_sena_icbf(): void
    {
        $calculator = new ContributionCalculator;
        $params = $this->params2026();

        $b = $calculator->calculate(
            ibc: self::SMLMV_2026,
            arlRiskClass: 1,
            contributorCode: '01',
            hasParafiscales: false,
            params: $params,
            smlmv: self::SMLMV_2026,
            periodDate: '2026-01-01',
        );

        $this->assertEquals(0.0, $b->senaAmount);
        $this->assertEquals(0.0, $b->icbfAmount);
        $this->assertEquals(0.0, $b->parafiscalAmount);
        $this->assertGreaterThan(0, $b->ccfAmount, 'CCF sí aplica a dependiente');
    }

    public function test_tipo_02_servicio_domestico_sin_parafiscales(): void
    {
        $calculator = new ContributionCalculator;
        $params = $this->params2026();

        $b = $calculator->calculate(
            ibc: self::SMLMV_2026,
            arlRiskClass: 1,
            contributorCode: '02',
            hasParafiscales: true,
            params: $params,
            smlmv: self::SMLMV_2026,
            periodDate: '2026-01-01',
        );

        $this->assertEquals(0.0, $b->senaAmount, 'Servicio doméstico exento SENA');
        $this->assertEquals(0.0, $b->icbfAmount, 'Servicio doméstico exento ICBF');
        $this->assertGreaterThan(0, $b->healthTotal);
        $this->assertGreaterThan(0, $b->ccfAmount);
    }
}
