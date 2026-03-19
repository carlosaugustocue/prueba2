<?php

namespace App\Modules\Authorizations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'appointment_request_id' => $this->appointment_request_id,
            'affiliate_id' => $this->affiliate_id,
            'eps_id' => $this->eps_id,
            'service_type' => $this->service_type,
            'diagnosis_or_reason' => $this->diagnosis_or_reason,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_badge_class' => $this->status?->badgeClass(),
            'radicated_at' => $this->radicated_at?->format('Y-m-d'),
            'radicated_at_formatted' => $this->radicated_at?->format('d/m/Y'),
            'radicado_number' => $this->radicado_number,
            'authorization_number' => $this->authorization_number,
            'authorized_ips_name' => $this->authorized_ips_name,
            'valid_until' => $this->valid_until?->format('Y-m-d'),
            'valid_until_formatted' => $this->valid_until?->format('d/m/Y'),
            'denial_reason' => $this->denial_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),

            'affiliate' => $this->whenLoaded('affiliate', fn () => new \App\Modules\Affiliates\Resources\AffiliateResource($this->affiliate)),
            'eps' => $this->whenLoaded('eps', fn () => ['id' => $this->eps->id, 'name' => $this->eps->name, 'code' => $this->eps->code ?? null]),
            'appointment_request' => $this->whenLoaded('appointmentRequest', function () {
                if (!$this->appointmentRequest) return null;
                $r = $this->appointmentRequest;
                return [
                    'id' => $r->id,
                    'specialty' => $r->specialty,
                    'status' => $r->status?->value ?? $r->getRawOriginal('status'),
                    'status_label' => $r->status?->label() ?? '—',
                    'has_appointment' => !is_null($r->appointment_id),
                ];
            }),
            'state_histories' => $this->whenLoaded('stateHistories', fn () => $this->stateHistories->map(fn ($h) => [
                'id' => $h->id,
                'from_status' => $h->from_status,
                'to_status' => $h->to_status,
                'user_id' => $h->user_id,
                'notes' => $h->notes,
                'created_at' => $h->created_at?->format('d/m/Y H:i'),
            ])),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'id' => $d->id,
                'type' => $d->type,
                'original_name' => $d->original_name,
                'mime_type' => $d->mime_type,
                'size' => $d->size,
                'created_at' => $d->created_at?->format('d/m/Y H:i'),
            ])),
        ];
    }
}
