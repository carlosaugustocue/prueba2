<?php

namespace App\Modules\SocialSecurity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Affiliates\Enums\DocumentType;

class UpdatePayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $payer = $this->route('payer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document_number' => ['required', 'string', 'max:20', Rule::unique('payers', 'document_number')->ignore($payer->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active') === false) {
            $this->merge(['is_active' => true]);
        }
    }
}
