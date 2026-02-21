<?php

namespace App\Modules\SocialSecurity\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'document_type' => $this->document_type?->value,
            'document_type_label' => $this->document_type?->label(),
            'document_type_abbreviation' => $this->document_type?->abbreviation(),
            'document_number' => $this->document_number,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'contact_person' => $this->contact_person,
            'is_active' => $this->is_active,
            'social_security_profiles_count' => $this->when(isset($this->social_security_profiles_count), $this->social_security_profiles_count),
            'social_security_profiles' => $this->whenLoaded('socialSecurityProfiles', function () {
                return $this->socialSecurityProfiles->map(fn ($p) => [
                    'id' => $p->id,
                    'affiliate_id' => $p->affiliate_id,
                    'affiliate' => $p->relationLoaded('affiliate') && $p->affiliate ? [
                        'id' => $p->affiliate->id,
                        'full_name' => $p->affiliate->full_name ?? trim(collect([
                            $p->affiliate->first_name,
                            $p->affiliate->second_name,
                            $p->affiliate->last_name,
                            $p->affiliate->second_last_name,
                        ])->filter()->join(' ')),
                        'document_number' => $p->affiliate->document_number,
                        'document_type_abbreviation' => $p->affiliate->document_type?->abbreviation(),
                    ] : null,
                ]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
