<?php

namespace App\Modules\PilaManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePortalCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', 'in:ARL,EPS,AFP,CCF'],
            'is_not_applicable' => ['required', 'boolean'],
            'username' => ['nullable', 'string', 'max:100', 'required_if:is_not_applicable,false'],
            'password' => ['nullable', 'string', 'min:6', 'required_if:is_not_applicable,false'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

