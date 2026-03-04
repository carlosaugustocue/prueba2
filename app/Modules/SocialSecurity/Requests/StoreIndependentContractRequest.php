<?php

namespace App\Modules\SocialSecurity\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndependentContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payer_id' => ['nullable', 'exists:payers,id'],
            'contract_reference' => ['nullable', 'string', 'max:100'],
            'contract_type' => ['required', 'string', 'max:40'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'risk_class' => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payer_id' => $this->filled('payer_id') ? $this->input('payer_id') : null,
            'contract_reference' => $this->filled('contract_reference') ? trim((string) $this->input('contract_reference')) : null,
            'end_date' => $this->filled('end_date') ? $this->input('end_date') : null,
            'risk_class' => $this->filled('risk_class') ? $this->input('risk_class') : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}

