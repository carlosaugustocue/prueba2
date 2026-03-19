<?php

namespace App\Modules\Affiliates\Requests;

use App\Modules\SocialSecurity\Models\PaymentOperator;
use App\Modules\SocialSecurity\Services\ContributionParametersResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Modules\Affiliates\Enums\DocumentType;

class RegisterAffiliateWithPilaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paymentStatuses = (array) config('pila.affiliation.payment_statuses', []);
        $paymentPeriodicities = (array) config('pila.affiliation.payment_periodicities', []);
        $billingTypes = (array) config('pila.affiliation.billing_types', []);

        $period = (string) ($this->input('last_payment_period') ?? '');
        if (preg_match('/^\d{6}$/', $period) === 1) {
            $year = (int) substr($period, 0, 4);
            $month = (int) substr($period, 4, 2);
            $periodDate = sprintf('%04d-%02d-01', $year, $month);
        } else {
            $periodDate = now()->format('Y-m-01');
        }

        $paramsResolver = app(ContributionParametersResolver::class);
        $ibcMin = $paramsResolver->getIbcMin($periodDate);
        $ibcMax = $paramsResolver->getIbcMax($periodDate);

        $ibcRules = ['required', 'numeric'];
        if ($ibcMin !== null) {
            $ibcRules[] = 'min:' . (string) $ibcMin;
        }
        if ($ibcMax !== null) {
            $ibcRules[] = 'max:' . (string) $ibcMax;
        }

        $pilaOperators = PaymentOperator::query()
            ->where('is_active', true)
            ->get(['name', 'code'])
            ->map(fn ($op) => $op->code ?: Str::lower(Str::slug($op->name, '')))
            ->values()
            ->all();

        return [
            // ----------------------- Afiliado -----------------------
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
            'patient_type' => ['required', 'in:cotizante'],
            'holder_id' => ['nullable'],
            'relationship_type' => ['nullable'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:500'],
            'eps_id' => ['required', 'exists:eps,id'],

            // ----------------------- Empleador -----------------------
            'employer_nit' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    $v = trim((string) $value);
                    $parts = explode('-', $v, 2);
                    $baseDigits = preg_replace('/\D/', '', $parts[0] ?? '');
                    if ($baseDigits === '') {
                        $fail('El NIT del empleador no es válido.');
                    }
                },
            ],
            'employer_name' => ['required', 'string', 'max:200'],
            'employer_address' => ['nullable', 'string', 'max:200'],
            'employer_city' => ['nullable', 'string', 'max:100'],
            'employer_department' => ['nullable', 'string', 'max:100'],
            'employer_phone' => ['nullable', 'string', 'max:20'],
            'employer_email' => ['nullable', 'email', 'max:150'],
            'employer_payment_business_day' => [
                'nullable',
                'integer',
                'min:' . config('pila.deadline.min_business_day', 2),
                'max:' . config('pila.deadline.max_business_day', 16),
            ],
            'employer_is_self_employed' => ['nullable', 'boolean'],
            'employer_is_active' => ['nullable', 'boolean'],

            // ----------------------- PILA -----------------------
            'cotizante_type_id' => ['nullable', 'exists:pila_cotizante_types,id'],
            'pila_operator' => ['nullable', 'string', 'max:30', Rule::in($pilaOperators)],
            'last_novelty_type' => ['nullable', 'string', 'max:10'],
            'last_novelty_date' => ['nullable', 'date'],
            'ibc' => $ibcRules,
            'pays_parafiscales' => ['boolean'],
            'self_employed' => ['boolean'],
            'risk_class_id' => ['nullable', 'exists:pila_risk_classes,id'],
            'arp_id' => ['nullable', 'exists:arps,id'],
            'afp_id' => ['nullable', 'exists:afps,id'],
            'ccf_id' => ['nullable', 'exists:ccfs,id'],
            'payment_periodicity' => ['nullable', Rule::in($paymentPeriodicities)],
            'billing_type' => ['nullable', Rule::in($billingTypes)],
            'last_document_number' => ['nullable', 'string', 'max:30'],
            'last_payment_period' => ['nullable', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'payment_status' => ['nullable', Rule::in($paymentStatuses)],

            // Siempre se crea como vigente; se fuerza en el service.
            'is_current' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['pays_parafiscales' => false, 'self_employed' => false] as $k => $v) {
            if ($this->has($k) === false) {
                $merge[$k] = $v;
            }
        }

        foreach (['employer_is_active' => true, 'employer_is_self_employed' => false] as $k => $v) {
            if ($this->has($k) === false) {
                $merge[$k] = $v;
            }
        }

        foreach (['patient_type' => 'cotizante', 'status' => 'ACTIVO'] as $k => $v) {
            if ($this->has($k) === false) {
                $merge[$k] = $v;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}

