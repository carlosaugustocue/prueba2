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
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'eps' => $this->whenLoaded('eps', fn() => [
                'id' => $this->eps->id,
                'name' => $this->eps->name,
                'code' => $this->eps->code,
            ]),
            'holder' => $this->whenLoaded('holder', fn() => [
                'id' => $this->holder->id,
                'full_name' => $this->holder->full_name,
            ]),
            'beneficiaries' => $this->whenLoaded('beneficiaries'),
            'appointments' => $this->whenLoaded('appointments'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
