<?php

namespace App\Modules\Patients\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Patients\Enums\DocumentType;
use App\Modules\Patients\Enums\PatientType;

class CreatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document_number' => ['required', 'string', 'max:20', 'unique:patients,document_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'eps_id' => ['required', 'exists:eps,id'],
            'patient_type' => ['required', Rule::enum(PatientType::class)],
            'holder_id' => ['nullable', 'required_if:patient_type,beneficiario', 'exists:patients,id'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.unique' => 'Este número de documento ya está registrado.',
            'holder_id.required_if' => 'Debe seleccionar un titular para el beneficiario.',
        ];
    }
}
