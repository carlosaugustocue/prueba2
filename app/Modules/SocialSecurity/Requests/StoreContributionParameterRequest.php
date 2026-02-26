<?php

namespace App\Modules\SocialSecurity\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContributionParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50', 'in:HEALTH,PENSION,ARL,CCF,SENA,ICBF,FSP,SYSTEM'],
            'subtype' => ['required', 'string', 'max:50'],
            'value' => ['required', 'numeric', 'min:0'],
            'value_type' => ['required', 'string', 'in:PERCENTAGE,AMOUNT,MULTIPLIER'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'description' => ['nullable', 'string', 'max:255'],
            'legal_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'valid_from' => 'vigencia desde',
            'valid_to' => 'vigencia hasta',
            'legal_reference' => 'referencia normativa',
        ];
    }
}
