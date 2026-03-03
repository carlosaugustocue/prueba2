<?php

namespace App\Modules\Appointments\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use Illuminate\Validation\Validator;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(AppointmentType::class)],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'specialty' => [
                'nullable',
                'string',
                'max:100',
                'required_if:type,specialist',
            ],
            'appointment_date' => ['nullable', 'date', 'after_or_equal:today'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'doctor_name' => ['nullable', 'string', 'max:150'],
            'location_name' => ['nullable', 'string', 'max:150'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'location_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\-\s()]{6,29}$/'],
            'authorization_number' => ['nullable', 'string', 'max:50'],
            'specifications' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $date = $this->input('appointment_date');
            $time = $this->input('appointment_time');

            if ($date && $time) {
                try {
                    $selected = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", config('app.timezone'));
                    if ($selected->isPast()) {
                        $validator->errors()->add('appointment_time', 'La hora debe ser actual o futura.');
                    }
                } catch (\Throwable) {
                    // ignore
                }
            }

            $type = $this->input('type');
            $specialty = $this->input('specialty');
            $isSpecialist = $type === AppointmentType::SPECIALIST->value
                || (is_string($type) && strtolower($type) === 'specialist');
            if ($isSpecialist && trim((string) $specialty) === '') {
                $validator->errors()->add('specialty', 'Cuando el tipo es Especialista, debe indicar la especialidad.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'specialty.required_if' => 'Cuando el tipo es Especialista, debe indicar la especialidad.',
            'appointment_date.after_or_equal' => 'La fecha debe ser hoy o una fecha futura.',
            'location_phone.regex' => 'El teléfono del prestador solo puede contener números y caracteres telefónicos válidos (+, -, paréntesis y espacios).',
        ];
    }
}
