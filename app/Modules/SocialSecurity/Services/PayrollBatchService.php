<?php

namespace App\Modules\SocialSecurity\Services;

use App\Modules\Affiliates\Enums\AffiliateType;
use App\Modules\Affiliates\Enums\AffiliateStatus;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\SocialSecurity\Enums\PayrollStatus;
use App\Modules\SocialSecurity\Models\Payroll;

/**
 * Generación y liquidación masiva de planillas por período (año/mes).
 * Solo cotizantes; los beneficiarios no llevan planilla propia.
 */
class PayrollBatchService
{
    public function __construct(
        private PayrollService $payrollService,
        private IndependentContractIbcService $independentContractIbcService
    ) {}

    /**
     * Crea planillas para todos los afiliados activos cotizantes con perfil SS válido para el año/mes.
     * Los beneficiarios se excluyen (están cubiertos por el titular, no generan planilla).
     *
     * @return array{created: int, skipped: int, errors: array<int, string>}
     */
    public function generateMonthlyPayrolls(int $year, int $month): array
    {
        $affiliates = Affiliate::where('status', AffiliateStatus::ACTIVO)
            ->where('patient_type', AffiliateType::COTIZANTE)
            ->whereHas('socialSecurityProfile')
            ->with('socialSecurityProfile')
            ->get();

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($affiliates as $affiliate) {
            try {
                $exists = Payroll::where('affiliate_id', $affiliate->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $profile = $affiliate->socialSecurityProfile;
                $validationErrors = $this->payrollService->validateProfileForPayroll($profile, $year, $month);
                if ($validationErrors !== []) {
                    $errors[$affiliate->id] = implode(' ', $validationErrors);
                    $skipped++;
                    continue;
                }

                $this->payrollService->getOrCreatePayroll($affiliate, $year, $month);
                $created++;
            } catch (\Throwable $e) {
                $errors[$affiliate->id] = $e->getMessage();
                $skipped++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Liquida (settle) todas las planillas PENDING del año/mes indicado.
     * Opcionalmente filtra por pagador.
     *
     * @return array{settled: int, errors: array<int, string>}
     */
    public function settleMonthlyPayrolls(int $year, int $month, ?int $payerId = null): array
    {
        $query = Payroll::where('year', $year)
            ->where('month', $month)
            ->where('status', PayrollStatus::PENDING->value)
            ->with(['affiliate.socialSecurityProfile.contributorType']);

        $payrolls = $query->get();
        $settled = 0;
        $errors = [];

        foreach ($payrolls as $payroll) {
            try {
                if ($payerId !== null && ! $this->matchesPayerFilter($payroll, $payerId)) {
                    continue;
                }
                $this->payrollService->settle($payroll);
                $settled++;
            } catch (\Throwable $e) {
                $errors[$payroll->id] = $e->getMessage();
            }
        }

        return [
            'settled' => $settled,
            'errors' => $errors,
        ];
    }

    private function matchesPayerFilter(Payroll $payroll, int $payerId): bool
    {
        $profile = $payroll->affiliate?->socialSecurityProfile;
        if ($profile === null) {
            return false;
        }

        if ((int) $profile->payer_id === $payerId) {
            return true;
        }

        $contributorCode = $profile->contributorType?->code ?? '';
        if (! in_array($contributorCode, ['03', '51', '59'], true)) {
            return false;
        }

        $affiliate = $payroll->affiliate;
        if ($affiliate === null) {
            return false;
        }

        $contractIbc = $this->independentContractIbcService->resolveForPeriod($affiliate, (int) $payroll->year, (int) $payroll->month);
        if ($contractIbc === null) {
            return false;
        }

        $payerIds = collect($contractIbc['contract_payer_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        return in_array($payerId, $payerIds, true);
    }
}
