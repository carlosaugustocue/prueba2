<?php

namespace Database\Seeders;

use App\Modules\SocialSecurity\Models\ContributionParameter;
use Illuminate\Database\Seeder;

/**
 * Carga los parámetros de aportes y sistema vigentes para 2026.
 * Todos los valores provienen de normativa; no hay magic numbers en código.
 * Referencia: docs/ARQUITECTURA_Y_PLAN_FEATURE_SEGURIDAD_SOCIAL.md, docs/NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md
 */
class ContributionParameterSeeder extends Seeder
{
    private const VALID_FROM_2026 = '2026-01-01';

    private const VALUE_TYPE_PERCENTAGE = 'PERCENTAGE';
    private const VALUE_TYPE_AMOUNT = 'AMOUNT';
    private const VALUE_TYPE_MULTIPLIER = 'MULTIPLIER';

    public function run(): void
    {
        $rows = $this->getParameterRows();

        foreach ($rows as $row) {
            ContributionParameter::updateOrCreate(
                [
                    'type' => $row['type'],
                    'subtype' => $row['subtype'],
                    'valid_from' => $row['valid_from'],
                ],
                [
                    'value' => $row['value'],
                    'value_type' => $row['value_type'],
                    'valid_to' => $row['valid_to'] ?? null,
                    'description' => $row['description'] ?? null,
                    'legal_reference' => $row['legal_reference'] ?? null,
                    'metadata' => $row['metadata'] ?? null,
                ]
            );
        }
    }

    /**
     * Definición de todos los parámetros vigentes 2026. Valores por ley.
     */
    private function getParameterRows(): array
    {
        $ref2025 = 'Decreto 1469/2025';
        $refDian = 'Resolución DIAN 000238/2025';
        $refLey2381 = 'Ley 2381/2024 (reforma pensional)';
        $refPila = 'Decreto 1990/2016 (PILA)';
        $refArl = 'Decreto 768/2022 (clasificación ARL)';

        return [
            // --- HEALTH (Salud) ---
            ['type' => 'HEALTH', 'subtype' => 'TOTAL', 'value' => 12.5, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Salud total (empleador + empleado)', 'legal_reference' => $ref2025],
            ['type' => 'HEALTH', 'subtype' => 'EMPLOYER', 'value' => 8.5, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Salud a cargo del empleador', 'legal_reference' => $ref2025],
            ['type' => 'HEALTH', 'subtype' => 'EMPLOYEE', 'value' => 4.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Salud a cargo del empleado', 'legal_reference' => $ref2025],
            ['type' => 'HEALTH', 'subtype' => 'INDEPENDENT', 'value' => 12.5, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Salud independiente (100% cotizante)', 'legal_reference' => $ref2025],

            // --- PENSION ---
            ['type' => 'PENSION', 'subtype' => 'TOTAL', 'value' => 16.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Pensión total', 'legal_reference' => $ref2025],
            ['type' => 'PENSION', 'subtype' => 'EMPLOYER', 'value' => 12.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Pensión empleador', 'legal_reference' => $ref2025],
            ['type' => 'PENSION', 'subtype' => 'EMPLOYEE', 'value' => 4.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Pensión empleado', 'legal_reference' => $ref2025],
            ['type' => 'PENSION', 'subtype' => 'INDEPENDENT', 'value' => 16.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Pensión independiente (100% cotizante)', 'legal_reference' => $ref2025],

            // --- ARL (clases de riesgo) ---
            ['type' => 'ARL', 'subtype' => 'RISK_1', 'value' => 0.522, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'ARL clase I - Riesgo mínimo', 'legal_reference' => $refArl],
            ['type' => 'ARL', 'subtype' => 'RISK_2', 'value' => 1.044, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'ARL clase II - Riesgo bajo', 'legal_reference' => $refArl],
            ['type' => 'ARL', 'subtype' => 'RISK_3', 'value' => 2.436, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'ARL clase III - Riesgo medio', 'legal_reference' => $refArl],
            ['type' => 'ARL', 'subtype' => 'RISK_4', 'value' => 4.350, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'ARL clase IV - Riesgo alto', 'legal_reference' => $refArl],
            ['type' => 'ARL', 'subtype' => 'RISK_5', 'value' => 6.960, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'ARL clase V - Riesgo máximo', 'legal_reference' => $refArl],

            // --- CCF, SENA, ICBF (parafiscales) ---
            ['type' => 'CCF', 'subtype' => 'TOTAL', 'value' => 4.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Caja de Compensación Familiar', 'legal_reference' => $ref2025],
            ['type' => 'SENA', 'subtype' => 'TOTAL', 'value' => 2.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'SENA', 'legal_reference' => $ref2025],
            ['type' => 'ICBF', 'subtype' => 'TOTAL', 'value' => 3.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'ICBF', 'legal_reference' => $ref2025],

            // --- Fondo de Solidaridad Pensional (escalonado por SMLMV) ---
            ['type' => 'FSP', 'subtype' => 'THRESHOLD_4_16', 'value' => 1.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'FSP: IBC entre 4 y menos de 16 SMLMV', 'legal_reference' => $refLey2381],
            ['type' => 'FSP', 'subtype' => 'THRESHOLD_16_17', 'value' => 1.2, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'FSP: IBC entre 16 y menos de 17 SMLMV', 'legal_reference' => $refLey2381],
            ['type' => 'FSP', 'subtype' => 'THRESHOLD_17_18', 'value' => 1.4, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'FSP: IBC entre 17 y menos de 18 SMLMV', 'legal_reference' => $refLey2381],
            ['type' => 'FSP', 'subtype' => 'THRESHOLD_18_19', 'value' => 1.6, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'FSP: IBC entre 18 y menos de 19 SMLMV', 'legal_reference' => $refLey2381],
            ['type' => 'FSP', 'subtype' => 'THRESHOLD_19_20', 'value' => 1.8, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'FSP: IBC entre 19 y menos de 20 SMLMV', 'legal_reference' => $refLey2381],
            ['type' => 'FSP', 'subtype' => 'THRESHOLD_20_PLUS', 'value' => 2.0, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'FSP: IBC 20 o más SMLMV', 'legal_reference' => $refLey2381],

            // --- SYSTEM (valores de referencia y rangos) ---
            ['type' => 'SYSTEM', 'subtype' => 'SMLMV', 'value' => 1750905, 'value_type' => self::VALUE_TYPE_AMOUNT, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Salario Mínimo Legal Mensual Vigente (pesos)', 'legal_reference' => $ref2025],
            ['type' => 'SYSTEM', 'subtype' => 'TRANSPORT_AID', 'value' => 249095, 'value_type' => self::VALUE_TYPE_AMOUNT, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Auxilio de transporte (pesos)', 'legal_reference' => $ref2025],
            ['type' => 'SYSTEM', 'subtype' => 'UVT', 'value' => 52374, 'value_type' => self::VALUE_TYPE_AMOUNT, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Unidad de Valor Tributario (pesos)', 'legal_reference' => $refDian],
            ['type' => 'SYSTEM', 'subtype' => 'IBC_MIN_SMLMV', 'value' => 1, 'value_type' => self::VALUE_TYPE_MULTIPLIER, 'valid_from' => self::VALID_FROM_2026, 'description' => 'IBC mínimo = 1 SMLMV', 'legal_reference' => $ref2025],
            ['type' => 'SYSTEM', 'subtype' => 'IBC_MAX_SMLMV', 'value' => 25, 'value_type' => self::VALUE_TYPE_MULTIPLIER, 'valid_from' => self::VALID_FROM_2026, 'description' => 'IBC máximo = 25 SMLMV', 'legal_reference' => $ref2025],
            ['type' => 'SYSTEM', 'subtype' => 'PAYMENT_DAY_MIN', 'value' => 2, 'value_type' => self::VALUE_TYPE_AMOUNT, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Día hábil mínimo de vencimiento PILA', 'legal_reference' => $refPila],
            ['type' => 'SYSTEM', 'subtype' => 'PAYMENT_DAY_MAX', 'value' => 16, 'value_type' => self::VALUE_TYPE_AMOUNT, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Día hábil máximo de vencimiento PILA', 'legal_reference' => $refPila],
            ['type' => 'SYSTEM', 'subtype' => 'PENSION_PILLAR_THRESHOLD_SMLMV', 'value' => 2.3, 'value_type' => self::VALUE_TYPE_MULTIPLIER, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Umbral pilar contributivo (Colpensiones/ACCAI) en SMLMV', 'legal_reference' => $refLey2381],
            ['type' => 'SYSTEM', 'subtype' => 'INDEPENDENT_IBC_PERCENT', 'value' => 40, 'value_type' => self::VALUE_TYPE_PERCENTAGE, 'valid_from' => self::VALID_FROM_2026, 'description' => 'Porcentaje del ingreso mensualizado para IBC de independientes con contratos múltiples', 'legal_reference' => 'Decreto 1273/2018 y concordantes'],
        ];
    }
}
