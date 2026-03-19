<?php

namespace App\Modules\PilaManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\PilaManagement\Services\DeadlineService;

class PilaAffiliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deadlineService = app(DeadlineService::class);

        $affiliate = $this->relationLoaded('affiliate') ? $this->affiliate : null;
        $employer = $this->relationLoaded('employer') ? $this->employer : null;

        // Día hábil: si el empleador no lo tiene configurado, se deriva del NIT (Decreto 1990 de 2016).
        $effectivePaymentBusinessDay = null;
        if ($employer && ! empty(trim((string) $employer->document_number))) {
            $effectivePaymentBusinessDay = $employer->payment_business_day
                ?? $deadlineService->paymentBusinessDayFromDocument($employer->document_number);
        }

        return [
            'id' => $this->id,
            'affiliate_id' => $this->affiliate_id,
            'affiliate' => $affiliate ? [
                'id' => $affiliate->id,
                'full_name' => $affiliate->full_name,
                'document_number' => $affiliate->document_number,
                'document_type' => $affiliate->document_type?->value,
                'document_type_abbreviation' => $affiliate->document_type?->abbreviation(),
            ] : null,
            'employer_id' => $this->employer_id,
            'employer' => $this->whenLoaded('employer', fn () => [
                'id' => $this->employer->id,
                'name' => $this->employer->name,
                'document_type' => $this->employer->document_type,
                'document_number' => $this->employer->document_number,
                'payment_business_day' => $this->employer->payment_business_day,
            ]),
            // Día hábil efectivo (configurado en empleador o derivado del NIT). Usado para mostrar y para calcular fecha límite.
            'effective_payment_business_day' => $effectivePaymentBusinessDay,
            'risk_class' => $this->whenLoaded('riskClass', fn () => [
                'id' => $this->riskClass->id,
                'level' => $this->riskClass->level,
                'class_name' => $this->riskClass->class_name,
                'description' => $this->riskClass->description,
            ]),
            'eps' => $this->whenLoaded('eps', fn () => $this->eps ? ['id' => $this->eps->id, 'name' => $this->eps->name, 'code' => $this->eps->code ?? null] : null),
            'afp' => $this->whenLoaded('afp', fn () => $this->afp ? ['id' => $this->afp->id, 'name' => $this->afp->name, 'code' => $this->afp->code ?? null] : null),
            'arp' => $this->whenLoaded('arp', fn () => $this->arp ? ['id' => $this->arp->id, 'name' => $this->arp->name, 'code' => $this->arp->code ?? null] : null),
            'ccf' => $this->whenLoaded('ccf', fn () => $this->ccf ? ['id' => $this->ccf->id, 'name' => $this->ccf->name, 'code' => $this->ccf->code ?? null] : null),
            'cotizante_type_id' => $this->cotizante_type_id,
            'cotizante_type' => $this->whenLoaded('cotizanteType', fn () => [
                'id' => $this->cotizanteType->id,
                'code' => $this->cotizanteType->code,
                'name' => $this->cotizanteType->name,
            ]),
            'pila_operator' => $this->pila_operator,
            'last_novelty_type' => $this->last_novelty_type,
            'last_novelty_date' => $this->last_novelty_date ? $this->last_novelty_date->toDateString() : null,
            'ibc' => $this->ibc,
            'pays_parafiscales' => (bool) $this->pays_parafiscales,
            'self_employed' => (bool) $this->self_employed,
            'risk_class_id' => $this->risk_class_id,
            'eps_id' => $this->eps_id,
            'afp_id' => $this->afp_id,
            'arp_id' => $this->arp_id,
            'ccf_id' => $this->ccf_id,
            'payment_periodicity' => $this->payment_periodicity,
            'billing_type' => $this->billing_type,
            'last_document_number' => $this->last_document_number,
            'last_payment_period' => $this->last_payment_period,
            'payment_status' => $this->payment_status,
            'is_current' => (bool) $this->is_current,

            // Indicadores operativos (Sprint 2.5).
            // Se calcula de forma derivada para mostrar “fecha límite estimada”.
            'next_due_date' => $effectivePaymentBusinessDay !== null
                ? $deadlineService->dueDateForPeriodByPaymentDay(
                    (int) now()->format('Y'),
                    (int) now()->format('n'),
                    (int) $effectivePaymentBusinessDay
                )->toDateString()
                : null,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

