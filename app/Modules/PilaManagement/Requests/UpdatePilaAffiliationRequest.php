<?php

namespace App\Modules\PilaManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePilaAffiliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Modules\PilaManagement\Models\PilaAffiliation $affiliation */
        $affiliation = $this->route('affiliation');

        return [
            'affiliate_id' => [
                'required',
                'exists:affiliates,id',
                Rule::unique('pila_affiliations', 'affiliate_id')->ignore($affiliation?->id),
            ],
            'employer_id' => ['nullable', 'exists:pila_employers,id'],
            'cotizante_type_id' => ['nullable', 'exists:pila_cotizante_types,id'],
            'pila_operator' => ['nullable', 'string', 'max:30'],
            'ibc' => ['nullable', 'numeric', 'min:0'],
            'pays_parafiscales' => ['boolean'],
            'self_employed' => ['boolean'],
            'risk_class_id' => ['nullable', 'exists:pila_risk_classes,id'],
            'eps_id' => ['nullable', 'exists:eps,id'],
            'afp_id' => ['nullable', 'exists:afps,id'],
            'arp_id' => ['nullable', 'exists:arps,id'],
            'ccf_id' => ['nullable', 'exists:ccfs,id'],
            'payment_periodicity' => ['nullable', 'string', 'max:20'],
            'billing_type' => ['nullable', 'string', 'max:30'],
            'last_document_number' => ['nullable', 'string', 'max:30'],
            'last_payment_period' => ['nullable', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'payment_status' => ['nullable', 'string', 'max:20'],
            'is_current' => ['boolean'],
        ];
    }
}

