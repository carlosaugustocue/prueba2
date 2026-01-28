<?php

namespace App\Modules\Patients\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Patients\Enums\DocumentType;
use App\Modules\Patients\Enums\PatientType;
use App\Modules\Patients\Enums\RelationshipType;
use App\Modules\Patients\Models\Patient;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $patient = $this->route('patient');
        $patientId = $patient->id;

        return [
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document_number' => ['required', 'string', 'max:20', Rule::unique('patients', 'document_number')->ignore($patientId)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'eps_id' => ['required', 'exists:eps,id'],
            'patient_type' => [
                'required',
                Rule::enum(PatientType::class),
                // No puede cambiar a beneficiario si tiene beneficiarios
                function ($attribute, $value, $fail) use ($patient) {
                    if ($value === 'beneficiario' && $patient->beneficiaries()->count() > 0) {
                        $fail('No puede cambiar a beneficiario porque tiene beneficiarios asociados.');
                    }
                },
            ],
            'holder_id' => [
                'nullable',
                'required_if:patient_type,beneficiario',
                'exists:patients,id',
                // No puede ser su propio holder
                function ($attribute, $value, $fail) use ($patientId) {
                    if ($value == $patientId) {
                        $fail('Un paciente no puede ser su propio cotizante.');
                    }
                },
                // Validar que el holder sea un cotizante
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('patient_type') === 'beneficiario') {
                        $holder = Patient::find($value);
                        if (!$holder) {
                            $fail('El cotizante seleccionado no existe.');
                            return;
                        }
                        if ($holder->patient_type->value !== 'cotizante') {
                            $fail('Solo puede seleccionar un cotizante como titular.');
                        }
                    }
                },
            ],
            'relationship_type' => [
                'nullable',
                'required_if:patient_type,beneficiario',
                Rule::enum(RelationshipType::class),
            ],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.unique' => 'Este número de documento ya está registrado.',
            'holder_id.required_if' => 'Debe seleccionar un cotizante titular para el beneficiario.',
            'relationship_type.required_if' => 'Debe seleccionar el tipo de parentesco con el cotizante.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Si es cotizante, asegurar que holder_id y relationship_type sean null
        if ($this->input('patient_type') === 'cotizante') {
            $this->merge([
                'holder_id' => null,
                'relationship_type' => null,
            ]);
        }
    }
}
