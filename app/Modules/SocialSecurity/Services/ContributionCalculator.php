<?php

namespace App\Modules\SocialSecurity\Services;

use App\Modules\SocialSecurity\DTOs\ContributionBreakdown;

/**
 * Cálculo puro de aportes. No accede a BD; recibe parámetros ya resueltos.
 * Todo se deriva de $params (desde ContributionParametersResolver); sin magic numbers.
 * Reglas por tipo de cotizante: ContributorTypeRules.
 */
class ContributionCalculator
{
    /**
     * Calcula el desglose de aportes para un período.
     *
     * @param  float  $ibc  Ingreso Base de Cotización
     * @param  int  $arlRiskClass  Clase de riesgo ARL (1-5; 0 si no aplica)
     * @param  string  $contributorCode  Código tipo cotizante PILA (01, 03, 59, etc.)
     * @param  bool  $hasParafiscales  Si el empleador paga parafiscales (SENA, ICBF) — solo aplica tipo 01
     * @param  array<string, array<string, float>>  $params  Parámetros vigentes de getParametersForDate()
     * @param  float  $smlmv  Salario mínimo vigente para el período
     * @param  float  $proportionalFactor  Factor 0-1 para tipo 51 (días/30 o semanas/4); 1.0 = mes completo
     */
    public function calculate(
        float $ibc,
        int $arlRiskClass,
        string $contributorCode,
        bool $hasParafiscales,
        array $params,
        float $smlmv,
        string $periodDate,
        float $proportionalFactor = 1.0,
    ): ContributionBreakdown {
        $rules = ContributorTypeRules::forCode($contributorCode);
        $isDependent = $rules['is_dependent'];
        $effectiveIbc = $ibc;
        if ($rules['is_proportional'] && $proportionalFactor > 0 && $proportionalFactor < 1) {
            $effectiveIbc = $ibc * $proportionalFactor;
        }

        $healthTotal = 0.0;
        $healthEmployer = 0.0;
        $healthEmployee = 0.0;
        if ($rules['health_applies']) {
            $healthTotal = $this->amountFromPercent($effectiveIbc, $this->param($params, 'HEALTH', $isDependent ? 'TOTAL' : 'INDEPENDENT'));
            $healthEmployer = $isDependent ? $this->amountFromPercent($effectiveIbc, $this->param($params, 'HEALTH', 'EMPLOYER')) : 0.0;
            $healthEmployee = $isDependent ? $this->amountFromPercent($effectiveIbc, $this->param($params, 'HEALTH', 'EMPLOYEE')) : $healthTotal;
        }

        $pensionTotal = 0.0;
        $pensionEmployer = 0.0;
        $pensionEmployee = 0.0;
        if ($rules['pension_applies']) {
            $pensionTotal = $this->amountFromPercent($effectiveIbc, $this->param($params, 'PENSION', $isDependent ? 'TOTAL' : 'INDEPENDENT'));
            $pensionEmployer = $isDependent ? $this->amountFromPercent($effectiveIbc, $this->param($params, 'PENSION', 'EMPLOYER')) : 0.0;
            $pensionEmployee = $isDependent ? $this->amountFromPercent($effectiveIbc, $this->param($params, 'PENSION', 'EMPLOYEE')) : $pensionTotal;
        }

        $arlAmount = 0.0;
        if ($rules['arl_applies'] && $arlRiskClass >= 1 && $arlRiskClass <= 5) {
            $arlRate = $this->param($params, 'ARL', 'RISK_' . $arlRiskClass);
            $arlAmount = $this->amountFromPercent($effectiveIbc, $arlRate);
        }

        $ccfAmount = 0.0;
        if ($rules['ccf_applies']) {
            $ccfAmount = $this->amountFromPercent($effectiveIbc, $this->param($params, 'CCF', 'TOTAL'));
        }

        $senaAmount = 0.0;
        $icbfAmount = 0.0;
        if ($rules['parafiscales_allowed'] && $hasParafiscales) {
            $senaAmount = $this->amountFromPercent($effectiveIbc, $this->param($params, 'SENA', 'TOTAL'));
            $icbfAmount = $this->amountFromPercent($effectiveIbc, $this->param($params, 'ICBF', 'TOTAL'));
        }
        $parafiscalAmount = $senaAmount + $icbfAmount;

        $fspAmount = $rules['pension_applies'] ? $this->calculateFsp($effectiveIbc, $smlmv, $params) : 0.0;

        $totalAmount = $healthTotal + $pensionTotal + $arlAmount + $ccfAmount + $parafiscalAmount + $fspAmount;

        $parametersUsed = [
            'health_rate' => $rules['health_applies'] ? $this->param($params, 'HEALTH', $isDependent ? 'TOTAL' : 'INDEPENDENT') : 0,
            'pension_rate' => $rules['pension_applies'] ? $this->param($params, 'PENSION', $isDependent ? 'TOTAL' : 'INDEPENDENT') : 0,
            'arl_rate' => $arlAmount > 0 ? $this->param($params, 'ARL', 'RISK_' . $arlRiskClass) : 0,
            'ccf_rate' => $rules['ccf_applies'] ? $this->param($params, 'CCF', 'TOTAL') : 0,
            'smlmv' => $smlmv,
            'proportional_factor' => $proportionalFactor,
        ];

        return new ContributionBreakdown(
            ibc: $effectiveIbc,
            contributorCode: $contributorCode,
            arlRiskClass: $arlRiskClass,
            healthTotal: $healthTotal,
            healthEmployer: $healthEmployer,
            healthEmployee: $healthEmployee,
            pensionTotal: $pensionTotal,
            pensionEmployer: $pensionEmployer,
            pensionEmployee: $pensionEmployee,
            arlAmount: $arlAmount,
            ccfAmount: $ccfAmount,
            senaAmount: $senaAmount,
            icbfAmount: $icbfAmount,
            parafiscalAmount: $parafiscalAmount,
            fspAmount: $fspAmount,
            totalAmount: $totalAmount,
            parametersUsed: $parametersUsed,
            periodDate: $periodDate,
        );
    }

    private function param(array $params, string $type, string $subtype): float
    {
        return (float) ($params[$type][$subtype] ?? 0);
    }

    private function amountFromPercent(float $base, float $percent): float
    {
        return round($base * ($percent / 100), 2);
    }

    /**
     * Fondo de Solidaridad Pensional: porcentaje escalonado según IBC en SMLMV.
     */
    private function calculateFsp(float $ibc, float $smlmv, array $params): float
    {
        if ($smlmv <= 0) {
            return 0.0;
        }
        $smlmvCount = $ibc / $smlmv;
        if ($smlmvCount < 4) {
            return 0.0;
        }

        $rate = match (true) {
            $smlmvCount < 16 => $this->param($params, 'FSP', 'THRESHOLD_4_16'),
            $smlmvCount < 17 => $this->param($params, 'FSP', 'THRESHOLD_16_17'),
            $smlmvCount < 18 => $this->param($params, 'FSP', 'THRESHOLD_17_18'),
            $smlmvCount < 19 => $this->param($params, 'FSP', 'THRESHOLD_18_19'),
            $smlmvCount < 20 => $this->param($params, 'FSP', 'THRESHOLD_19_20'),
            default => $this->param($params, 'FSP', 'THRESHOLD_20_PLUS'),
        };

        return $this->amountFromPercent($ibc, $rate);
    }
}
