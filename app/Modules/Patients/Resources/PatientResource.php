<?php

namespace App\Modules\Patients\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'document_type' => $this->document_type?->value,
            'document_type_label' => $this->document_type?->label(),
            'document_type_abbreviation' => $this->document_type?->abbreviation(),
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'whatsapp_number' => $this->getWhatsAppNumber(),
            'email' => $this->email,
            'address' => $this->address,
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
            'eps' => $this->whenLoaded('eps', fn() => [
                'id' => $this->eps->id,
                'name' => $this->eps->name,
                'code' => $this->eps->code,
            ]),
            'eps_id' => $this->eps_id,
            'holder' => $this->whenLoaded('holder', fn() => $this->holder ? [
                'id' => $this->holder->id,
                'full_name' => $this->holder->full_name,
                'document_type_abbreviation' => $this->holder->document_type?->abbreviation(),
                'document_number' => $this->holder->document_number,
                'phone' => $this->holder->phone,
                'whatsapp' => $this->holder->whatsapp,
            ] : null),
            'beneficiaries' => $this->whenLoaded('beneficiaries', fn() => 
                $this->beneficiaries->map(fn($b) => [
                    'id' => $b->id,
                    'full_name' => $b->full_name,
                    'document_type_abbreviation' => $b->document_type?->abbreviation(),
                    'document_number' => $b->document_number,
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
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
