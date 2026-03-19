<?php

namespace App\Modules\Affiliates\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Affiliates\Enums\DocumentType;
use App\Modules\Affiliates\Enums\AffiliateType;
use App\Modules\Affiliates\Enums\RelationshipType;
use App\Modules\Affiliates\Models\Affiliate;

class CreateAffiliateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document_number' => ['required', 'string', 'max:20', 'unique:affiliates,document_number'],
            'document_issue_date' => ['nullable', 'date'],
            'first_name' => ['required', 'string', 'max:100'],
            'second_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'second_last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'phone_2' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'eps_id' => ['nullable', 'required_if:patient_type,beneficiario', 'exists:eps,id'],
            'patient_type' => ['required', Rule::enum(AffiliateType::class)],
            'holder_id' => [
                'nullable',
                'required_if:patient_type,beneficiario',
                'exists:affiliates,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('patient_type') === 'beneficiario') {
                        $holder = Affiliate::with(['socialSecurityProfile', 'pilaAffiliation'])->find($value);
                        if (! $holder) {
                            $fail('El cotizante seleccionado no existe.');
                            return;
                        }
                        if ($holder->patient_type->value !== 'cotizante') {
                            $fail('Solo puede seleccionar un cotizante como titular.');
                            return;
                        }
                        $holderEpsId = $holder->pilaAffiliation?->eps_id ?? $holder->socialSecurityProfile?->eps_id;
                        if (! $holderEpsId) {
                            $fail('El cotizante no tiene EPS asignada. Configure la afiliación en Afiliaciones PILA.');
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
            'gender' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'string', 'max:30'],
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
        if ($this->input('patient_type') === 'cotizante') {
            $this->merge([
                'holder_id' => null,
                'relationship_type' => null,
            ]);
        }

        if ($this->input('patient_type') === 'beneficiario' && $this->filled('holder_id')) {
            $holder = Affiliate::with(['socialSecurityProfile', 'pilaAffiliation'])->find($this->input('holder_id'));
            $holderEpsId = $holder?->pilaAffiliation?->eps_id ?? $holder?->socialSecurityProfile?->eps_id;
            if ($holderEpsId) {
                $this->merge(['eps_id' => $holderEpsId]);
            }
        }
    }
}
