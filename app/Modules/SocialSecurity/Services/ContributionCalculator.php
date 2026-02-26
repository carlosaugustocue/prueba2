<?php

namespace App\Modules\SocialSecurity\Services;

use App\Modules\SocialSecurity\DTOs\ContributionBreakdown;

/**
 * Cálculo puro de aportes. No accede a BD; recibe parámetros ya resueltos.
 * Todo se deriva de $params (desde ContributionParametersResolver); sin magic numbers.
 */
class ContributionCalculator
{
    /** Códigos tipo cotizante que pagan split empleador/empleado (dependientes). */
    private const DEPENDENT_CODES = ['01', '02'];

    /**
     * Calcula el desglose de aportes para un período.
     *
     * @param  float  $ibc  Ingreso Base de Cotización
     * @param  int  $arlRiskClass  Clase de riesgo ARL (1-5; 0 si no aplica)
     * @param  string  $contributorCode  Código tipo cotizante PILA (01, 03, 59, etc.)
     * @param  bool  $hasParafiscales  Si el empleador paga parafiscales (SENA, ICBF)
     * @param  array<string, array<string, float>>  $params  Parámetros vigentes de getParametersForDate()
     * @param  float  $smlmv  Salario mínimo vigente para el período
     */
    public function calculate(
        float $ibc,
        int $arlRiskClass,
        string $contributorCode,
        bool $hasParafiscales,
        array $params,
        float $smlmv,
        string $periodDate,
    ): ContributionBreakdown {
        $isDependent = in_array($contributorCode, self::DEPENDENT_CODES, true);

        $healthTotal = $this->amountFromPercent($ibc, $this->param($params, 'HEALTH', $isDependent ? 'TOTAL' : 'INDEPENDENT'));
        $healthEmployer = $isDependent ? $this->amountFromPercent($ibc, $this->param($params, 'HEALTH', 'EMPLOYER')) : 0.0;
        $healthEmployee = $isDependent ? $this->amountFromPercent($ibc, $this->param($params, 'HEALTH', 'EMPLOYEE')) : $healthTotal;

        $pensionTotal = $this->amountFromPercent($ibc, $this->param($params, 'PENSION', $isDependent ? 'TOTAL' : 'INDEPENDENT'));
        $pensionEmployer = $isDependent ? $this->amountFromPercent($ibc, $this->param($params, 'PENSION', 'EMPLOYER')) : 0.0;
        $pensionEmployee = $isDependent ? $this->amountFromPercent($ibc, $this->param($params, 'PENSION', 'EMPLOYEE')) : $pensionTotal;

        $arlAmount = 0.0;
        if ($arlRiskClass >= 1 && $arlRiskClass <= 5) {
            $arlRate = $this->param($params, 'ARL', 'RISK_' . $arlRiskClass);
            $arlAmount = $this->amountFromPercent($ibc, $arlRate);
        }

        $ccfAmount = $isDependent ? $this->amountFromPercent($ibc, $this->param($params, 'CCF', 'TOTAL')) : 0.0;

        $senaAmount = 0.0;
        $icbfAmount = 0.0;
        if ($isDependent && $hasParafiscales) {
            $senaAmount = $this->amountFromPercent($ibc, $this->param($params, 'SENA', 'TOTAL'));
            $icbfAmount = $this->amountFromPercent($ibc, $this->param($params, 'ICBF', 'TOTAL'));
        }
        $parafiscalAmount = $senaAmount + $icbfAmount;

        $fspAmount = $this->calculateFsp($ibc, $smlmv, $params);

        $totalAmount = $healthTotal + $pensionTotal + $arlAmount + $ccfAmount + $parafiscalAmount + $fspAmount;

        $parametersUsed = [
            'health_rate' => $this->param($params, 'HEALTH', $isDependent ? 'TOTAL' : 'INDEPENDENT'),
            'pension_rate' => $this->param($params, 'PENSION', $isDependent ? 'TOTAL' : 'INDEPENDENT'),
            'arl_rate' => $arlRiskClass >= 1 && $arlRiskClass <= 5 ? $this->param($params, 'ARL', 'RISK_' . $arlRiskClass) : 0,
            'ccf_rate' => $isDependent ? $this->param($params, 'CCF', 'TOTAL') : 0,
            'smlmv' => $smlmv,
        ];

        return new ContributionBreakdown(
            ibc: $ibc,
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
