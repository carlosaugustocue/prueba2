<?php

namespace App\Modules\AdminUsers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->name === 'admin';
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'Seleccione un rol para el usuario.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}

