<?php

namespace App\Modules\SocialSecurity\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Patients\Models\Affiliate;
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
    ) {}

    /**
     * Obtiene o crea la planilla del afiliado para el período. Si no existe, la crea con PENDING.
     */
    public function getOrCreatePayroll(Affiliate $affiliate, int $year, int $month): Payroll
    {
        $payroll = Payroll::where('affiliate_id', $affiliate->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($payroll !== null) {
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
        ]);
    }

    /**
     * Pre-calcula el desglose de aportes sin guardar (para simulación en UI).
     */
    public function preview(SocialSecurityProfile $profile, int $year, int $month): ContributionBreakdown
    {
        $profile->loadMissing('contributorType');
        $periodDate = sprintf('%04d-%02d-01', $year, $month);
        $params = $this->paramsResolver->getParametersForDate($periodDate);
        $smlmv = $this->paramsResolver->getSmlmv($periodDate) ?? 0.0;

        $ibc = (float) ($profile->ibc ?? 0);
        $arlRiskClass = $this->normalizeArlRiskClass($profile->arp_risk_class);
        $contributorCode = $profile->contributorType?->code ?? '03';
        $hasParafiscales = (bool) $profile->has_parafiscales;

        return $this->calculator->calculate(
            ibc: $ibc,
            arlRiskClass: $arlRiskClass,
            contributorCode: $contributorCode,
            hasParafiscales: $hasParafiscales,
            params: $params,
            smlmv: $smlmv,
            periodDate: $periodDate,
        );
    }

    /**
     * Liquida la planilla: calcula montos con parámetros vigentes del período, guarda y pasa a SETTLED.
     */
    public function settle(Payroll $payroll): ContributionBreakdown
    {
        if ($payroll->getStatusEnum() === PayrollStatus::PAID) {
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

        $ibc = (float) $profile->ibc;
        $arlRiskClass = $this->normalizeArlRiskClass($profile->arp_risk_class);
        $contributorCode = $profile->contributorType?->code ?? '03';
        $hasParafiscales = (bool) $profile->has_parafiscales;

        $breakdown = $this->calculator->calculate(
            ibc: $ibc,
            arlRiskClass: $arlRiskClass,
            contributorCode: $contributorCode,
            hasParafiscales: $hasParafiscales,
            params: $params,
            smlmv: $smlmv,
            periodDate: $periodDate,
        );

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

        if ($profile->ibc === null || $profile->ibc === '') {
            $errors[] = 'IBC no definido.';
        } elseif ($ibcMin !== null && $ibcMax !== null) {
            $ibc = (float) $profile->ibc;
            if ($ibc < $ibcMin || $ibc > $ibcMax) {
                $errors[] = sprintf('IBC fuera de rango vigente (%.0f - %.0f).', $ibcMin, $ibcMax);
            }
        }

        $profile->loadMissing('contributorType');
        if ($profile->contributorType === null) {
            $errors[] = 'Tipo de cotizante no asignado.';
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

    private function normalizeArlRiskClass(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $n = (int) $value;

        return $n >= 1 && $n <= 5 ? $n : 0;
    }
}
