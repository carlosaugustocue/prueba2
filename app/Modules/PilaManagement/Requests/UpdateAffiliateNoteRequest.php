<?php

namespace App\Modules\PilaManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAffiliateNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['affiliation', 'payment', 'general'])],
            'content' => ['required', 'string', 'min:2', 'max:5000'],
            'is_pinned' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('is_pinned')) {
            $this->merge(['is_pinned' => false]);
        }
    }
}

