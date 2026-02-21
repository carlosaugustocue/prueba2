<?php

namespace App\Modules\SocialSecurity\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoveltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'novelty_type_id' => ['required', 'exists:novelty_types,id'],
            'effective_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'old_value' => ['nullable', 'string', 'max:255'],
            'new_value' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'novelty_type_id' => 'tipo de novedad',
            'effective_date' => 'fecha efectiva',
            'description' => 'descripción',
            'old_value' => 'valor anterior',
            'new_value' => 'valor nuevo',
        ];
    }
}
