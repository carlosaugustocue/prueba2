<?php

namespace App\Modules\SocialSecurity\DTOs;

/**
 * Desglose de aportes calculado. Inmutable; usado para preview, liquidación y auditoría.
 */
final readonly class ContributionBreakdown
{
    public function __construct(
        public float $ibc,
        public string $contributorCode,
        public int $arlRiskClass,
        public float $healthTotal,
        public float $healthEmployer,
        public float $healthEmployee,
        public float $pensionTotal,
        public float $pensionEmployer,
        public float $pensionEmployee,
        public float $arlAmount,
        public float $ccfAmount,
        public float $senaAmount,
        public float $icbfAmount,
        public float $parafiscalAmount,
        public float $fspAmount,
        public float $totalAmount,
        public array $parametersUsed,
        public string $periodDate,
    ) {}

    /**
     * Para guardar en calculation_metadata y respuestas API.
     */
    public function toArray(): array
    {
        return [
            'ibc' => $this->ibc,
            'contributor_code' => $this->contributorCode,
            'arl_risk_class' => $this->arlRiskClass,
            'health_total' => round($this->healthTotal, 2),
            'health_employer' => round($this->healthEmployer, 2),
            'health_employee' => round($this->healthEmployee, 2),
            'pension_total' => round($this->pensionTotal, 2),
            'pension_employer' => round($this->pensionEmployer, 2),
            'pension_employee' => round($this->pensionEmployee, 2),
            'arl_amount' => round($this->arlAmount, 2),
            'ccf_amount' => round($this->ccfAmount, 2),
            'sena_amount' => round($this->senaAmount, 2),
            'icbf_amount' => round($this->icbfAmount, 2),
            'parafiscal_amount' => round($this->parafiscalAmount, 2),
            'fsp_amount' => round($this->fspAmount, 2),
            'total_amount' => round($this->totalAmount, 2),
            'parameters_used' => $this->parametersUsed,
            'period_date' => $this->periodDate,
        ];
    }
}
