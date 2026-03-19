<?php

namespace App\Modules\SocialSecurity\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\SocialSecurity\DTOs\ContributionBreakdown;
use App\Modules\SocialSecurity\Enums\PayrollStatus;
use App\Modules\SocialSecurity\Models\Payroll;
use App\Modules\SocialSecurity\Models\PayrollTracking;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Orquesta la creación, liquidación y cambios de estado de planillas.
 * Usa ContributionParametersResolver y ContributionCalculator; sin magic numbers.
 */
class PayrollService
{
    public function __construct(
        private ContributionCalculator $calculator,
        private ContributionParametersResolver $paramsResolver,
        private DueDateCalculator $dueDateCalculator,
        private IndependentContractIbcService $independentContractIbcService,
    ) {}

    /**
     * Obtiene o crea la planilla del afiliado para el período. Si no existe, la crea con PENDING.
     * Para tipo cotizante 51 (independiente flexible) puede indicarse días trabajados en el mes.
     */
    public function getOrCreatePayroll(Affiliate $affiliate, int $year, int $month, ?int $daysWorked = null): Payroll
    {
        $payroll = Payroll::where('affiliate_id', $affiliate->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($payroll !== null) {
            if ($daysWorked !== null) {
                $payroll->update(['days_worked' => $daysWorked]);
            }

            return $payroll;
        }

        $profile = $affiliate->socialSecurityProfile;
        $paymentDay = 2;
        $documentNumber = $affiliate->document_number ?? '';

        if ($profile?->payment_day !== null) {
            $paymentDay = (int) $profile->payment_day;
        } elseif ($documentNumber !== '') {
            $paymentDay = $this->dueDateCalculator->paymentDayFromDocument($documentNumber);
        }

        $dueDate = $this->dueDateCalculator->dueDateForPeriodByPaymentDay($year, $month, $paymentDay);

        return Payroll::create([
            'affiliate_id' => $affiliate->id,
            'year' => $year,
            'month' => $month,
            'due_date' => $dueDate,
            'status' => PayrollStatus::PENDING->value,
            'days_worked' => $daysWorked,
        ]);
    }

    /**
     * Pre-calcula el desglose de aportes sin guardar (para simulación en UI).
     * Para tipo 51 puede indicarse days_worked (1-30) para aportes proporcionales.
     */
    public function preview(SocialSecurityProfile $profile, int $year, int $month, ?int $daysWorked = null): ContributionBreakdown
    {
        $profile->loadMissing(['contributorType', 'affiliate']);
        $periodDate = sprintf('%04d-%02d-01', $year, $month);
        $params = $this->paramsResolver->getParametersForDate($periodDate);
        $smlmv = $this->paramsResolver->getSmlmv($periodDate) ?? 0.0;

        $contributorCode = $profile->contributorType?->code ?? '03';
        $ibcResolution = $this->resolveIbcForPeriod(
            $profile,
            $year,
            $month,
            $contributorCode
        );
        $arlRiskClass = $this->resolveArlRiskClass($profile, $ibcResolution);
        $ibc = $ibcResolution['ibc'];
        $hasParafiscales = (bool) $profile->has_parafiscales;

        $proportionalFactor = 1.0;
        if ($contributorCode === '51' && $daysWorked !== null && $daysWorked > 0) {
            $proportionalFactor = min(1.0, max(0.0, $daysWorked / 30));
        }

        $breakdown = $this->calculator->calculate(
            ibc: $ibc,
            arlRiskClass: $arlRiskClass,
            contributorCode: $contributorCode,
            hasParafiscales: $hasParafiscales,
            params: $params,
            smlmv: $smlmv,
            periodDate: $periodDate,
            proportionalFactor: $proportionalFactor,
        );

        return $this->enrichBreakdownWithIbcSource($breakdown, $ibcResolution);
    }

    /**
     * Liquida la planilla: calcula montos con parámetros vigentes del período, guarda y pasa a SETTLED.
     */
    public function settle(Payroll $payroll): ContributionBreakdown
    {
        if ($payroll->status === PayrollStatus::PAID) {
            throw new InvalidArgumentException('No se puede liquidar una planilla ya pagada.');
        }

        $payroll->loadMissing(['affiliate.socialSecurityProfile.contributorType']);
        $affiliate = $payroll->affiliate;
        $profile = $affiliate->socialSecurityProfile;

        if ($profile === null) {
            throw new InvalidArgumentException('El afiliado no tiene perfil de seguridad social.');
        }

        $errors = $this->validateProfileForPayroll($profile, $payroll->year, $payroll->month);
        if ($errors !== []) {
            throw new InvalidArgumentException('Perfil inválido para liquidar: ' . implode(' ', $errors));
        }

        $periodDate = sprintf('%04d-%02d-01', $payroll->year, $payroll->month);
        $params = $this->paramsResolver->getParametersForDate($periodDate);
        $smlmv = $this->paramsResolver->getSmlmv($periodDate) ?? 0.0;

        $contributorCode = $profile->contributorType?->code ?? '03';
        $ibcResolution = $this->resolveIbcForPeriod(
            $profile,
            $payroll->year,
            $payroll->month,
            $contributorCode
        );
        $arlRiskClass = $this->resolveArlRiskClass($profile, $ibcResolution);
        $ibc = $ibcResolution['ibc'];
        $hasParafiscales = (bool) $profile->has_parafiscales;
        $proportionalFactor = $this->proportionalFactorForSettle($contributorCode, $payroll);

        $breakdown = $this->calculator->calculate(
            ibc: $ibc,
            arlRiskClass: $arlRiskClass,
            contributorCode: $contributorCode,
            hasParafiscales: $hasParafiscales,
            params: $params,
            smlmv: $smlmv,
            periodDate: $periodDate,
            proportionalFactor: $proportionalFactor,
        );
        $breakdown = $this->enrichBreakdownWithIbcSource($breakdown, $ibcResolution);

        $meta = $breakdown->toArray();
        $meta['calculated_at'] = now()->toIso8601String();
        $meta['calculated_by'] = auth()->id();

        $oldStatus = $payroll->status;
        $payroll->update([
            'health_amount' => $breakdown->healthTotal,
            'pension_amount' => $breakdown->pensionTotal,
            'arl_amount' => $breakdown->arlAmount,
            'ccf_amount' => $breakdown->ccfAmount,
            'parafiscal_amount' => $breakdown->parafiscalAmount,
            'fsp_amount' => $breakdown->fspAmount,
            'total_amount' => $breakdown->totalAmount,
            'calculation_metadata' => $meta,
            'status' => PayrollStatus::SETTLED->value,
        ]);

        PayrollTracking::create([
            'payroll_id' => $payroll->id,
            'event' => 'status_change',
            'user_id' => auth()->id(),
            'old_status' => $oldStatus,
            'new_status' => PayrollStatus::SETTLED->value,
            'metadata' => null,
        ]);

        return $breakdown;
    }

    /**
     * Valida que el perfil tenga datos mínimos para generar/liquidar planilla.
     *
     * @return string[] Lista de mensajes de error; vacío si es válido
     */
    public function validateProfileForPayroll(SocialSecurityProfile $profile, int $year, int $month): array
    {
        $errors = [];
        $periodDate = sprintf('%04d-%02d-01', $year, $month);

        $ibcMin = $this->paramsResolver->getIbcMin($periodDate);
        $ibcMax = $this->paramsResolver->getIbcMax($periodDate);

        $profile->loadMissing(['contributorType', 'affiliate']);
        $contributorCode = $profile->contributorType?->code ?? '';

        $ibcSource = null;
        if ($profile->ibc !== null && $profile->ibc !== '') {
            $ibcSource = (float) $profile->ibc;
        } else {
            $ibcResolution = $this->resolveIbcForPeriod($profile, $year, $month, $contributorCode);
            if (($ibcResolution['source'] ?? 'profile') === 'contracts') {
                $ibcSource = (float) $ibcResolution['ibc'];
            }
        }

        if ($ibcSource === null) {
            $errors[] = 'IBC no definido. Configure IBC en perfil o registre contratos independientes activos para el período.';
        } elseif ($ibcMin !== null && $ibcMax !== null) {
            $ibc = $ibcSource;
            if ($ibc < $ibcMin || $ibc > $ibcMax) {
                $errors[] = sprintf('IBC fuera de rango vigente (%.0f - %.0f).', $ibcMin, $ibcMax);
            }
        }
        if ($this->canUseIndependentContracts($contributorCode) && $profile->affiliate !== null) {
            $contractIbc = $this->independentContractIbcService->resolveForPeriod($profile->affiliate, $year, $month);
            if ($contractIbc !== null && (int) ($contractIbc['contracts_without_payer_count'] ?? 0) > 0) {
                $errors[] = 'Hay contratos independientes activos sin pagador asignado. Asigne pagador para evitar inconsistencias en filtros y reportes.';
            }
        }

        if ($profile->contributorType === null) {
            $errors[] = 'Tipo de cotizante no asignado.';
        } elseif (! ContributorTypeRules::isSupported($profile->contributorType->code ?? '')) {
            $errors[] = 'Tipo de cotizante ' . ($profile->contributorType->code ?? '') . ' no soportado para liquidación.';
        } else {
            $code = $profile->contributorType->code ?? '';
            if ($code === '51') {
                $smlmv = $this->paramsResolver->getSmlmv($periodDate);
                if ($smlmv !== null && $ibcSource !== null && (float) $ibcSource >= $smlmv) {
                    $errors[] = 'Para tipo 51 (independiente flexible) el IBC debe ser menor a 1 SMLMV según Circular 093/2025.';
                }
            }
        }

        return $errors;
    }

    /**
     * Cambia el estado de la planilla y registra el evento en payroll_trackings.
     */
    public function transitionStatus(Payroll $payroll, PayrollStatus $newStatus, ?User $user = null): void
    {
        $oldStatus = $payroll->status;

        $updates = ['status' => $newStatus->value];
        if ($newStatus === PayrollStatus::PAID) {
            $updates['paid_at'] = now();
        }
        if ($newStatus === PayrollStatus::SENT_TO_CLIENT) {
            $updates['sent_at'] = now();
        }

        $payroll->update($updates);

        PayrollTracking::create([
            'payroll_id' => $payroll->id,
            'event' => 'status_change',
            'user_id' => $user?->id ?? auth()->id(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus->value,
            'metadata' => null,
        ]);
    }

    /**
     * Marca como OVERDUE las planillas con vencimiento pasado que no están pagadas ni enviadas.
     *
     * @return int Cantidad de planillas actualizadas
     */
    public function markOverduePayrolls(): int
    {
        $today = Carbon::today()->toDateString();

        return Payroll::where('due_date', '<', $today)
            ->whereNotIn('status', [PayrollStatus::PAID->value, PayrollStatus::SENT_TO_CLIENT->value])
            ->update(['status' => PayrollStatus::OVERDUE->value]);
    }

    /**
     * Factor proporcional para tipo 51 (independiente flexible): días trabajados / 30.
     * Si no es tipo 51 o days_worked es null, devuelve 1.0 (mes completo).
     */
    private function proportionalFactorForSettle(string $contributorCode, Payroll $payroll): float
    {
        if ($contributorCode !== '51') {
            return 1.0;
        }
        $days = $payroll->days_worked;
        if ($days === null || $days <= 0) {
            return 1.0;
        }
        $factor = $days / 30;

        return min(1.0, max(0.0, (float) $factor));
    }

    private function normalizeArlRiskClass(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $n = (int) $value;

        return $n >= 1 && $n <= 5 ? $n : 0;
    }

    private function resolveArlRiskClass(SocialSecurityProfile $profile, array $ibcResolution): int
    {
        $riskFromProfile = $this->normalizeArlRiskClass($profile->arp_risk_class);
        $riskFromContracts = isset($ibcResolution['contracts']['max_risk_class'])
            ? $this->normalizeArlRiskClass($ibcResolution['contracts']['max_risk_class'])
            : 0;

        return max($riskFromProfile, $riskFromContracts);
    }

    /**
     * Resuelve el IBC para el período:
     * - Perfil SS (manual), o
     * - Consolidado de contratos activos (independientes).
     *
     * @return array{
     *   ibc: float,
     *   source: 'profile'|'contracts',
     *   contracts: array<string,mixed>|null
     * }
     */
    private function resolveIbcForPeriod(
        SocialSecurityProfile $profile,
        int $year,
        int $month,
        string $contributorCode
    ): array {
        if ($this->canUseIndependentContracts($contributorCode)) {
            $affiliate = $profile->affiliate;
            if ($affiliate !== null) {
                $contractIbc = $this->independentContractIbcService->resolveForPeriod($affiliate, $year, $month);
                if ($contractIbc !== null) {
                    return [
                        'ibc' => (float) $contractIbc['ibc'],
                        'source' => 'contracts',
                        'contracts' => $contractIbc,
                    ];
                }
            }
        }

        return [
            'ibc' => (float) ($profile->ibc ?? 0),
            'source' => 'profile',
            'contracts' => null,
        ];
    }

    private function canUseIndependentContracts(string $contributorCode): bool
    {
        return in_array($contributorCode, ['03', '51', '59'], true);
    }

    private function enrichBreakdownWithIbcSource(ContributionBreakdown $breakdown, array $ibcResolution): ContributionBreakdown
    {
        $paramsUsed = $breakdown->parametersUsed;
        $paramsUsed['ibc_source'] = $ibcResolution['source'] ?? 'profile';
        if (($ibcResolution['source'] ?? null) === 'contracts' && isset($ibcResolution['contracts'])) {
            $paramsUsed['contracts'] = $ibcResolution['contracts'];
        }

        return new ContributionBreakdown(
            ibc: $breakdown->ibc,
            contributorCode: $breakdown->contributorCode,
            arlRiskClass: $breakdown->arlRiskClass,
            healthTotal: $breakdown->healthTotal,
            healthEmployer: $breakdown->healthEmployer,
            healthEmployee: $breakdown->healthEmployee,
            pensionTotal: $breakdown->pensionTotal,
            pensionEmployer: $breakdown->pensionEmployer,
            pensionEmployee: $breakdown->pensionEmployee,
            arlAmount: $breakdown->arlAmount,
            ccfAmount: $breakdown->ccfAmount,
            senaAmount: $breakdown->senaAmount,
            icbfAmount: $breakdown->icbfAmount,
            parafiscalAmount: $breakdown->parafiscalAmount,
            fspAmount: $breakdown->fspAmount,
            totalAmount: $breakdown->totalAmount,
            parametersUsed: $paramsUsed,
            periodDate: $breakdown->periodDate,
        );
    }
}
