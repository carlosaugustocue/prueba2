<?php

namespace App\Modules\PilaManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PilaAffiliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $affiliate = $this->relationLoaded('affiliate') ? $this->affiliate : null;

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
            ]),
            'cotizante_type_id' => $this->cotizante_type_id,
            'cotizante_type' => $this->whenLoaded('cotizanteType', fn () => [
                'id' => $this->cotizanteType->id,
                'code' => $this->cotizanteType->code,
                'name' => $this->cotizanteType->name,
            ]),
            'pila_operator' => $this->pila_operator,
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
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

