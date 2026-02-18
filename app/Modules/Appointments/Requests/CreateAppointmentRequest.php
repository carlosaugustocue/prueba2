<?php

namespace App\Modules\Appointments\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use App\Modules\Patients\Models\Affiliate;

class CreateAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

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
                    $status = strtoupper(trim((string) ($affiliate->status ?? '')));
                    if ($status === 'INACTIVO' || $status === 'SUSPENDIDO') {
                        $fail('No se pueden crear citas para un afiliado inactivo o suspendido.');
                    }
                },
            ],
            'type' => ['required', Rule::enum(AppointmentType::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'specialty' => [
                'nullable',
                'string',
                'max:100',
                'required_if:type,specialist',
            ],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'doctor_name' => ['nullable', 'string', 'max:150'],
            'location_name' => ['nullable', 'string', 'max:150'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'location_phone' => ['nullable', 'string', 'max:30'],
            'authorization_number' => ['nullable', 'string', 'max:50'],
            'specifications' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:500'],
            'send_confirmation' => ['boolean'],
            'appointment_request_id' => ['nullable', 'exists:appointment_requests,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'affiliate_id.required' => 'Debe seleccionar un afiliado.',
            'affiliate_id.exists' => 'El afiliado seleccionado no es válido.',
            'type.required' => 'Debe seleccionar el tipo de cita.',
            'priority.required' => 'Debe seleccionar la prioridad.',
            'specialty.required_if' => 'Cuando el tipo es Especialista, debe indicar la especialidad.',
            'appointment_date.required' => 'Debe seleccionar la fecha de la cita.',
            'appointment_date.after_or_equal' => 'La fecha debe ser hoy o una fecha futura.',
            'appointment_time.required' => 'Debe seleccionar la hora de la cita.',
            'appointment_time.date_format' => 'La hora no tiene un formato válido (HH:mm).',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $date = $this->input('appointment_date');
            $time = $this->input('appointment_time');

            if (! $date || ! $time) {
                return;
            }

            try {
                $selected = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", config('app.timezone'));
            } catch (\Throwable) {
                return;
            }

            if ($selected->isPast()) {
                $validator->errors()->add('appointment_time', 'La hora debe ser actual o futura.');
            }
        });
    }
}
