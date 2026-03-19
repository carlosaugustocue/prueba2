<?php

namespace App\Modules\PilaManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PilaEmployerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'check_digit' => $this->check_digit,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'department' => $this->department,
            'phone' => $this->phone,
            'email' => $this->email,
            'payment_business_day' => $this->payment_business_day,
            'is_active' => (bool) $this->is_active,
            'is_self_employed' => (bool) $this->is_self_employed,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

