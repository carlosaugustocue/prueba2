<?php

namespace App\Modules\Patients\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Patients\Enums\DocumentType;
use App\Modules\Patients\Enums\PatientType;
use App\Modules\Patients\Enums\RelationshipType;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\SocialSecurity\Services\ContributionParametersResolver;

class CreateAffiliateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $resolver = app(ContributionParametersResolver::class);
        $date = now();
        $ibcMin = $resolver->getIbcMin($date) ?? 0;
        $ibcMax = $resolver->getIbcMax($date) ?? 999999999;
        $paymentBounds = $resolver->getPaymentDayBounds($date);

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
            'eps_id' => ['required', 'exists:eps,id'],
            'afp_id' => ['nullable', 'exists:afps,id'],
            'arp_id' => ['nullable', 'exists:arps,id'],
            'arp_risk_class' => ['nullable', 'string', 'max:20'],
            'ccf_id' => ['nullable', 'exists:ccfs,id'],
            'payment_operator_id' => ['nullable', 'exists:payment_operators,id'],
            'accounting_registry_id' => ['nullable', 'exists:accounting_registries,id'],
            'payer_id' => ['nullable', 'exists:payers,id'],
            'contributor_type_id' => ['nullable', 'exists:contributor_types,id'],
            'ibc' => ['nullable', 'numeric', 'min:'.$ibcMin, 'max:'.$ibcMax],
            'payment_day' => ['nullable', 'integer', 'min:'.$paymentBounds['min'], 'max:'.$paymentBounds['max']],
            'payment_periodicity' => ['nullable', 'string', 'in:CURRENT,OVERDUE'],
            'has_parafiscales' => ['boolean'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'patient_type' => ['required', Rule::enum(PatientType::class)],
            'holder_id' => [
                'nullable',
                'required_if:patient_type,beneficiario',
                'exists:affiliates,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('patient_type') === 'beneficiario') {
                        $holder = Affiliate::with('socialSecurityProfile')->find($value);
                        if (! $holder) {
                            $fail('El cotizante seleccionado no existe.');
                            return;
                        }
                        if ($holder->patient_type->value !== 'cotizante') {
                            $fail('Solo puede seleccionar un cotizante como titular.');
                            return;
                        }
                        if (! $holder->socialSecurityProfile?->eps_id) {
                            $fail('El cotizante no tiene EPS asignada. Primero asigne una EPS al cotizante.');
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
            $holder = Affiliate::with('socialSecurityProfile')->find($this->input('holder_id'));
            if ($holder && $holder->socialSecurityProfile?->eps_id) {
                $this->merge([
                    'eps_id' => $holder->socialSecurityProfile->eps_id,
                ]);
            }
        }

        $this->merge([
            'client_type_id' => $this->filled('client_type_id') ? $this->input('client_type_id') : null,
            'contributor_type_id' => $this->filled('contributor_type_id') ? $this->input('contributor_type_id') : null,
            'afp_id' => $this->filled('afp_id') ? $this->input('afp_id') : null,
            'arp_id' => $this->filled('arp_id') ? $this->input('arp_id') : null,
            'ccf_id' => $this->filled('ccf_id') ? $this->input('ccf_id') : null,
            'payment_operator_id' => $this->filled('payment_operator_id') ? $this->input('payment_operator_id') : null,
            'accounting_registry_id' => $this->filled('accounting_registry_id') ? $this->input('accounting_registry_id') : null,
            'payer_id' => $this->filled('payer_id') ? $this->input('payer_id') : null,
            'ibc' => $this->filled('ibc') ? $this->input('ibc') : null,
            'payment_day' => $this->filled('payment_day') ? $this->input('payment_day') : null,
            'payment_periodicity' => $this->filled('payment_periodicity') ? $this->input('payment_periodicity') : null,
            'has_parafiscales' => $this->boolean('has_parafiscales'),
            'observations' => $this->filled('observations') ? $this->input('observations') : null,
        ]);
    }
}
