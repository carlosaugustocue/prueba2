<?php

namespace App\Modules\PilaManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePilaEmployerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedDocTypes = config('pila.employer.allowed_document_types', []);

        return [
            'document_type' => ['required', 'string', Rule::in($allowedDocTypes)],
            'document_number' => ['required', 'string', 'max:20', Rule::unique('pila_employers', 'document_number')->where('document_type', $this->input('document_type'))],
            'check_digit' => ['nullable', 'string', 'max:1'],
            'name' => ['required', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'payment_business_day' => ['nullable', 'integer', 'min:' . config('pila.deadline.min_business_day', 2), 'max:' . config('pila.deadline.max_business_day', 16)],
            'is_active' => ['boolean'],
            'is_self_employed' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('is_active') === false) {
            $merge['is_active'] = true;
        }
        if ($this->has('is_self_employed') === false) {
            $merge['is_self_employed'] = false;
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}

