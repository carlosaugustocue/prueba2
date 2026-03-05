<?php

namespace App\Modules\SocialSecurity\Services;

use App\Modules\Patients\Models\Affiliate;
use App\Modules\SocialSecurity\Models\IndependentContract;

class IndependentContractIbcService
{
    public function __construct(private ContributionParametersResolver $resolver) {}

    /**
     * Calcula IBC sugerido desde contratos independientes activos en el período.
     *
     * Regla por defecto en Colombia para independientes: IBC = 40% del ingreso mensualizado.
     * Se deja parametrizable con SYSTEM/INDEPENDENT_IBC_PERCENT.
     *
     * @return array{
     *   ibc: float,
     *   ibc_raw: float,
     *   ibc_percent: float,
     *   total_monthly_income: float,
     *   max_risk_class: int|null,
     *   contracts_count: int,
     *   contract_ids: array<int>,
     *   contract_payer_ids: array<int>,
     *   contracts_without_payer_count: int
     * }|null
     */
    public function resolveForPeriod(Affiliate $affiliate, int $year, int $month): ?array
    {
        $contracts = IndependentContract::query()
            ->where('affiliate_id', $affiliate->id)
            ->activeForPeriod($year, $month)
            ->get(['id', 'payer_id', 'monthly_income', 'risk_class']);

        if ($contracts->isEmpty()) {
            return null;
        }

        $totalMonthlyIncome = (float) $contracts->sum(fn ($c) => (float) $c->monthly_income);
        if ($totalMonthlyIncome <= 0) {
            return null;
        }
        $maxRiskClass = $contracts
            ->filter(fn ($c) => ! empty($c->risk_class))
            ->max(fn ($c) => (int) $c->risk_class);
        $contractPayerIds = $contracts
            ->pluck('payer_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $contractsWithoutPayerCount = $contracts->filter(fn ($c) => empty($c->payer_id))->count();

        $periodDate = sprintf('%04d-%02d-01', $year, $month);
        $ibcPercent = $this->resolver->getIndependentIbcPercent($periodDate) ?? 40.0;
        $ibcRaw = round($totalMonthlyIncome * ($ibcPercent / 100), 2);
        $ibcMin = $this->resolver->getIbcMin($periodDate);
        $ibcMax = $this->resolver->getIbcMax($periodDate);

        $ibc = $ibcRaw;
        if ($ibcMin !== null && $ibc < $ibcMin) {
            $ibc = (float) $ibcMin;
        }
        if ($ibcMax !== null && $ibc > $ibcMax) {
            $ibc = (float) $ibcMax;
        }

        return [
            'ibc' => (float) round($ibc, 2),
            'ibc_raw' => (float) $ibcRaw,
            'ibc_percent' => (float) $ibcPercent,
            'total_monthly_income' => (float) round($totalMonthlyIncome, 2),
            'max_risk_class' => $maxRiskClass ? (int) $maxRiskClass : null,
            'contracts_count' => $contracts->count(),
            'contract_ids' => $contracts->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'contract_payer_ids' => $contractPayerIds,
            'contracts_without_payer_count' => (int) $contractsWithoutPayerCount,
        ];
    }
}

