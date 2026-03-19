<?php

namespace App\Modules\Appointments\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use App\Modules\Patients\Enums\AffiliateStatus;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\AppointmentRequests\Models\AppointmentRequest;
use App\Modules\Authorizations\Enums\AuthorizationStatus;

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
                    if (! $affiliate->status->isActive()) {
                        $fail(__('social_security.validation.affiliate_inactive'));
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
            'location_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\-\s()]{6,29}$/'],
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
            'location_phone.regex' => 'El teléfono del prestador solo puede contener números y caracteres telefónicos válidos (+, -, paréntesis y espacios).',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $requestId = $this->input('appointment_request_id');
            $date = $this->input('appointment_date');
            $time = $this->input('appointment_time');

            // RF-AUT-12: si la cita viene de una solicitud que requiere autorización, validar autorización aprobada y vigente
            if ($requestId) {
                $request = AppointmentRequest::with('authorization')->find($requestId);
                if ($request?->requires_authorization) {
                    if (! $request->authorization) {
                        $validator->errors()->add('appointment_request_id', 'Esta solicitud requiere una autorización EPS aprobada. Cree o vincule una autorización desde la ficha de la solicitud.');
                        return;
                    }
                    if ($request->authorization->status !== AuthorizationStatus::APPROVED) {
                        $validator->errors()->add('appointment_request_id', 'La autorización vinculada no está aprobada. Solo puede crear la cita cuando la EPS haya aprobado la autorización.');
                        return;
                    }
                    if ($request->authorization->valid_until && $request->authorization->valid_until->isPast()) {
                        $validator->errors()->add('appointment_request_id', 'La autorización está vencida. Solicite renovación ante la EPS antes de agendar la cita.');
                        return;
                    }
                }
            }

            // RF-AUT-15: fecha de cita no puede ser posterior a la vigencia de la autorización
            if ($requestId && $date) {
                $request = $request ?? AppointmentRequest::with('authorization')->find($requestId);
                if ($request?->authorization?->valid_until) {
                    $validUntil = $request->authorization->valid_until->format('Y-m-d');
                    if ($date > $validUntil) {
                        $validator->errors()->add('appointment_date', 'La fecha de la cita no puede ser posterior a la vigencia de la autorización (' . $request->authorization->valid_until->format('d/m/Y') . '). Requiere renovación ante la EPS.');
                    }
                }
            }

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
