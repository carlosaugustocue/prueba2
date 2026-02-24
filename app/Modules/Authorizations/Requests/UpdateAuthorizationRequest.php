<?php

namespace App\Modules\Authorizations\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Authorizations\Enums\AuthorizationStatus;
use App\Modules\Authorizations\Models\Authorization;

class UpdateAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $auth = $this->route('authorization');

        $rules = [
            'status' => ['sometimes', 'string', 'in:' . implode(',', array_column(AuthorizationStatus::cases(), 'value'))],
            'radicated_at' => ['nullable', 'date'],
            'radicado_number' => ['nullable', 'string', 'max:100'],
            'authorization_number' => ['nullable', 'string', 'max:100'],
            'authorized_ips_name' => ['nullable', 'string', 'max:255'],
            'valid_until' => ['nullable', 'date'],
            'denial_reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($auth && $this->input('status') === AuthorizationStatus::APPROVED->value) {
            $rules['authorization_number'] = ['required_with:status', 'string', 'max:100'];
            $rules['valid_until'] = ['required_with:status', 'date', 'after_or_equal:today'];
        }
        if ($auth && $this->input('status') === AuthorizationStatus::DENIED->value) {
            $rules['denial_reason'] = ['required_with:status', 'string'];
        }

        return $rules;
    }
}
