<?php

namespace App\Modules\AdminUsers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->name === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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

