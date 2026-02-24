<?php

namespace App\Modules\Patients\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->resource->relationLoaded('socialSecurityProfile') ? $this->socialSecurityProfile : null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'document_type' => $this->document_type?->value,
            'document_type_label' => $this->document_type?->label(),
            'document_type_abbreviation' => $this->document_type?->abbreviation(),
            'document_number' => $this->document_number,
            'document_issue_date' => $this->document_issue_date?->format('Y-m-d'),
            'document_issue_date_formatted' => $this->document_issue_date?->format('d/m/Y'),
            'first_name' => $this->first_name,
            'second_name' => $this->second_name,
            'last_name' => $this->last_name,
            'second_last_name' => $this->second_last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'phone_2' => $this->phone_2,
            'whatsapp' => $this->whatsapp,
            'whatsapp_number' => $this->getWhatsAppNumber(),
            'email' => $this->email,
            'address' => $this->address,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'department' => $this->department,
            'gender' => $this->gender,
            'client_type_id' => $profile?->client_type_id,
            'client_type' => $profile?->clientType?->name,
            'contributor_type_id' => $profile?->contributor_type_id,
            'contributor_type' => $profile?->contributorType?->name,
            'contributor_type_code' => $profile?->contributorType?->code,
            'afp_id' => $profile?->afp_id,
            'afp_name' => $profile?->afp?->name,
            'arp_id' => $profile?->arp_id,
            'arp_name' => $profile?->arp?->name,
            'arp_risk_class' => $profile?->arp_risk_class,
            'ccf_id' => $profile?->ccf_id,
            'ccf_name' => $profile?->ccf?->name,
            'payment_operator_id' => $profile?->payment_operator_id,
            'payment_operator' => $profile?->paymentOperator?->name,
            'accounting_registry_id' => $profile?->accounting_registry_id,
            'accounting_registry' => $profile?->accountingRegistry?->name,
            'payer_id' => $profile?->payer_id,
            'payer' => $profile && $profile->relationLoaded('payer') && $profile->payer ? [
                'id' => $profile->payer->id,
                'name' => $profile->payer->name,
                'document_number' => $profile->payer->document_number,
                'document_type_abbreviation' => $profile->payer->document_type?->abbreviation(),
            ] : null,
            'ibc' => $profile?->ibc ? (float) $profile->ibc : null,
            'payment_day' => $profile?->payment_day,
            'payment_periodicity' => $profile?->payment_periodicity,
            'has_parafiscales' => $profile?->has_parafiscales ?? false,
            'observations' => $profile?->observations,
            'status' => $this->status,
            'patient_type' => $this->patient_type?->value,
            'patient_type_label' => $this->patient_type?->label(),
            'is_holder' => $this->patient_type?->value === 'cotizante',
            'is_beneficiary' => $this->patient_type?->value === 'beneficiario',
            'holder_id' => $this->holder_id,
            'relationship_type' => $this->relationship_type?->value,
            'relationship_type_label' => $this->relationship_type?->label(),
            'relationship_type_short' => $this->relationship_type?->shortLabel(),
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'eps' => $this->whenLoaded('socialSecurityProfile', fn() => $this->socialSecurityProfile?->eps ? [
                'id' => $this->socialSecurityProfile->eps->id,
                'name' => $this->socialSecurityProfile->eps->name,
                'code' => $this->socialSecurityProfile->eps->code,
            ] : null),
            'eps_id' => $profile?->eps_id,
            'holder' => $this->whenLoaded('holder', fn() => $this->holder ? [
                'id' => $this->holder->id,
                'full_name' => $this->holder->full_name,
                'document_type_abbreviation' => $this->holder->document_type?->abbreviation(),
                'document_number' => $this->holder->document_number,
                'document_issue_date' => $this->holder->document_issue_date?->format('Y-m-d'),
                'document_issue_date_formatted' => $this->holder->document_issue_date?->format('d/m/Y'),
                'phone' => $this->holder->phone,
                'phone_2' => $this->holder->phone_2,
                'whatsapp' => $this->holder->whatsapp,
                'address' => $this->holder->address,
                'neighborhood' => $this->holder->neighborhood,
                'eps_id' => $this->holder->socialSecurityProfile?->eps_id,
            ] : null),
            'beneficiaries' => $this->whenLoaded('beneficiaries', fn() =>
                $this->beneficiaries->map(fn($b) => [
                    'id' => $b->id,
                    'full_name' => $b->full_name,
                    'document_type_abbreviation' => $b->document_type?->abbreviation(),
                    'document_number' => $b->document_number,
                    'document_issue_date' => $b->document_issue_date?->format('Y-m-d'),
                    'document_issue_date_formatted' => $b->document_issue_date?->format('d/m/Y'),
                    'phone' => $b->phone,
                    'whatsapp' => $b->whatsapp,
                    'birth_date' => $b->birth_date?->format('Y-m-d'),
                    'relationship_type' => $b->relationship_type?->value,
                    'relationship_type_label' => $b->relationship_type?->label(),
                    'relationship_type_short' => $b->relationship_type?->shortLabel(),
                ])
            ),
            'beneficiaries_count' => $this->whenLoaded('beneficiaries', fn() => $this->beneficiaries->count()),
            'appointments' => $this->whenLoaded('appointments', fn() =>
                $this->appointments->map(fn($apt) => [
                    'id' => $apt->id,
                    'type' => $apt->type?->value,
                    'type_label' => $apt->type?->label(),
                    'status' => $apt->status?->value,
                    'status_label' => $apt->status?->label(),
                    'status_badge_class' => $apt->status?->badgeClass(),
                    'priority' => $apt->priority?->value,
                    'priority_label' => $apt->priority?->label(),
                    'appointment_date' => $apt->appointment_date?->format('Y-m-d'),
                    'appointment_date_formatted' => $apt->appointment_date?->format('d/m/Y'),
                    'appointment_time' => $apt->appointment_time ? substr($apt->appointment_time, 0, 5) : null,
                    'formatted_datetime' => $apt->formatted_datetime,
                    'doctor_name' => $apt->doctor_name,
                    'location_name' => $apt->location_name,
                ])
            ),
            'authorizations' => $this->whenLoaded('authorizations', fn () =>
                $this->authorizations->map(fn ($a) => [
                    'id' => $a->id,
                    'uuid' => $a->uuid,
                    'status' => $a->status?->value,
                    'status_label' => $a->status?->label(),
                    'status_badge_class' => $a->status?->badgeClass(),
                    'service_type' => $a->service_type,
                    'authorization_number' => $a->authorization_number,
                    'valid_until' => $a->valid_until?->format('Y-m-d'),
                    'valid_until_formatted' => $a->valid_until?->format('d/m/Y'),
                ])
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'novelties' => $this->whenLoaded('novelties', fn () =>
                $this->novelties->map(fn ($n) => [
                    'id' => $n->id,
                    'novelty_type_id' => $n->novelty_type_id,
                    'novelty_type' => $n->noveltyType ? ['id' => $n->noveltyType->id, 'name' => $n->noveltyType->name, 'code' => $n->noveltyType->code] : null,
                    'effective_date' => $n->effective_date?->format('Y-m-d'),
                    'effective_date_formatted' => $n->effective_date?->format('d/m/Y'),
                    'description' => $n->description,
                    'old_value' => $n->old_value,
                    'new_value' => $n->new_value,
                ])
            ),
        ];
    }
}
