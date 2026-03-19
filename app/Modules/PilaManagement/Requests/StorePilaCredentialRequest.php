<?php

namespace App\Modules\PilaManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePilaCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorización a nivel de rutas/middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'operator' => ['required', 'string', 'max:30'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

