<?php

namespace App\Modules\AppointmentRequests\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use App\Modules\Affiliates\Enums\AffiliateStatus;
use App\Modules\Affiliates\Models\Affiliate;

class CreateAppointmentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'affiliate_id' => [
                'required',
                'exists:affiliates,id',
                function ($attribute, $value, $fail) {
                    $affiliate = Affiliate::find($value);
                    if (! $affiliate) {
                        return;
                    }
                    if (! $affiliate->status->isActive()) {
                        $fail(__('social_security.validation.affiliate_inactive'));
                        return;
                    }
                    if (! Affiliate::where('id', $value)->whereServiconliManaged()->exists()) {
                        $fail('Solo se pueden crear solicitudes de cita para afiliados con Serviconli como pagador (o tipo de cliente SERVICONLI). Este afiliado no cumple esa condición.');
                    }
                },
            ],
            'type' => ['required', Rule::enum(AppointmentType::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'specialty' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(fn () => $this->input('type') === AppointmentType::SPECIALIST->value),
            ],
            'requires_authorization' => ['nullable', 'boolean'],
            'client_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'affiliate_id.required' => 'Debe seleccionar un afiliado.',
            'affiliate_id.exists' => 'El afiliado seleccionado no es válido.',
            'type.required' => 'Debe seleccionar el tipo de cita.',
            'priority.required' => 'Debe seleccionar la prioridad.',
            'specialty.required' => 'Cuando el tipo es Especialista, debe indicar la especialidad.',
            'specialty.max' => 'La especialidad no puede superar 100 caracteres.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $specialty = $this->input('specialty');
            $isSpecialist = $type === AppointmentType::SPECIALIST->value
                || (is_string($type) && strtolower($type) === 'specialist');
            if ($isSpecialist && trim((string) $specialty) === '') {
                $validator->errors()->add(
                    'specialty',
                    'Cuando el tipo es Especialista, debe indicar la especialidad.'
                );
            }
        });
    }
}
