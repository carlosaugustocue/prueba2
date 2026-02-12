<?php

namespace App\Modules\AppointmentRequests\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use App\Modules\Patients\Models\Patient;

class CreateAppointmentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'exists:patients,id',
                function ($attribute, $value, $fail) {
                    $patient = Patient::find($value);
                    if (!$patient) {
                        return;
                    }
                    $status = strtoupper(trim((string) ($patient->status ?? '')));
                    if ($status === 'INACTIVO' || $status === 'SUSPENDIDO') {
                        $fail('No se pueden crear solicitudes de cita para un paciente inactivo o suspendido.');
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
            'client_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Debe seleccionar un paciente.',
            'patient_id.exists' => 'El paciente seleccionado no es válido.',
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
