<?php

namespace App\Modules\Authorizations\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Authorizations\Enums\AuthorizationStatus;
use App\Modules\Affiliates\Models\Affiliate;

class StoreAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_request_id' => ['nullable', 'exists:appointment_requests,id'],
            'affiliate_id' => ['required', 'exists:affiliates,id'],
            'eps_id' => ['nullable', 'exists:eps,id'], // Se toma del perfil del afiliado si no se envía
            'service_type' => ['required', 'string', 'max:100'],
            'diagnosis_or_reason' => ['nullable', 'string'],
            'radicated_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $affiliateId = $this->input('affiliate_id');
            if (!$affiliateId) {
                return;
            }
            $affiliate = Affiliate::with('socialSecurityProfile')->find($affiliateId);
            if (!$affiliate?->socialSecurityProfile?->eps_id) {
                $validator->errors()->add('affiliate_id', 'El afiliado no tiene EPS configurada en su perfil de seguridad social. Configure la EPS del afiliado antes de crear la autorización.');
            }
        });
    }

    public function getData(): array
    {
        $data = $this->validated();
        // EPS siempre desde el perfil del afiliado
        $affiliate = Affiliate::with('socialSecurityProfile')->find($data['affiliate_id']);
        $data['eps_id'] = $affiliate?->socialSecurityProfile?->eps_id;
        $data['status'] = AuthorizationStatus::PENDING_RADICATION;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        return $data;
    }
}
