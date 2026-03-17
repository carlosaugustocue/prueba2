<?php

namespace App\Modules\SocialSecurity\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $contractPayerIds = collect(data_get($this->calculation_metadata, 'parameters_used.contracts.contract_payer_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'affiliate_id' => $this->affiliate_id,
            'year' => $this->year,
            'month' => $this->month,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            'health_amount' => $this->health_amount !== null ? (float) $this->health_amount : null,
            'pension_amount' => $this->pension_amount !== null ? (float) $this->pension_amount : null,
            'arl_amount' => $this->arl_amount !== null ? (float) $this->arl_amount : null,
            'ccf_amount' => $this->ccf_amount !== null ? (float) $this->ccf_amount : null,
            'parafiscal_amount' => $this->parafiscal_amount !== null ? (float) $this->parafiscal_amount : null,
            'fsp_amount' => $this->fsp_amount !== null ? (float) $this->fsp_amount : null,
            'total_amount' => $this->total_amount !== null ? (float) $this->total_amount : null,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'notes' => $this->notes,
            'calculation_metadata' => $this->when($this->calculation_metadata, $this->calculation_metadata),
            'ibc_source' => data_get($this->calculation_metadata, 'parameters_used.ibc_source', 'profile'),
            'contract_payer_ids' => $contractPayerIds,
            'contracts_without_payer_count' => (int) data_get($this->calculation_metadata, 'parameters_used.contracts.contracts_without_payer_count', 0),
            'affiliate' => $this->whenLoaded('affiliate', function () {
                $a = $this->affiliate;
                return [
                    'id' => $a->id,
                    'full_name' => $a->full_name ?? trim(collect([
                        $a->first_name,
                        $a->second_name,
                        $a->last_name,
                        $a->second_last_name,
                    ])->filter()->join(' ')),
                    'document_number' => $a->document_number,
                    'document_type' => $a->document_type?->value,
                ];
            }),
            'affiliate_profile' => $this->whenLoaded('affiliate.socialSecurityProfile', function () {
                $p = $this->affiliate->socialSecurityProfile;
                if (! $p) return null;
                return [
                    'payer_id' => $p->payer_id,
                    'payer' => $p->relationLoaded('payer') && $p->payer ? [
                        'id' => $p->payer->id,
                        'name' => $p->payer->name,
                        'document_number' => $p->payer->document_number,
                    ] : null,
                ];
            }),
            'trackings' => $this->whenLoaded('trackings', fn () => $this->trackings->map(fn ($t) => [
                'id' => $t->id,
                'event' => $t->event,
                'old_status' => $t->old_status,
                'new_status' => $t->new_status,
                'created_at' => $t->created_at?->toIso8601String(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
