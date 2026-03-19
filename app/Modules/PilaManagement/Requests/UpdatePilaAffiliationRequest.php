<?php

namespace App\Modules\PilaManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\SocialSecurity\Models\PaymentOperator;
use App\Modules\SocialSecurity\Services\ContributionParametersResolver;
use Illuminate\Support\Str;

class UpdatePilaAffiliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Modules\PilaManagement\Models\PilaAffiliation $affiliation */
        $affiliation = $this->route('affiliation');

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

        $ibcRules = ['nullable', 'numeric'];
        if ($ibcMin !== null) {
            $ibcRules[] = 'min:' . (string) $ibcMin;
        } else {
            $ibcRules[] = 'min:0';
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
            'affiliate_id' => [
                'required',
                'exists:affiliates,id',
                Rule::unique('pila_affiliations', 'affiliate_id')->ignore($affiliation?->id),
            ],
            'employer_id' => ['nullable', 'exists:pila_employers,id'],
            'cotizante_type_id' => ['nullable', 'exists:pila_cotizante_types,id'],
            'pila_operator' => ['nullable', 'string', 'max:30', Rule::in($pilaOperators)],
            'last_novelty_type' => ['nullable', 'string', 'max:10'],
            'last_novelty_date' => ['nullable', 'date'],
            'ibc' => $ibcRules,
            'pays_parafiscales' => ['boolean'],
            'self_employed' => ['boolean'],
            'risk_class_id' => ['nullable', 'exists:pila_risk_classes,id'],
            'eps_id' => ['nullable', 'exists:eps,id'],
            'afp_id' => ['nullable', 'exists:afps,id'],
            'arp_id' => ['nullable', 'exists:arps,id'],
            'ccf_id' => ['nullable', 'exists:ccfs,id'],
            'payment_periodicity' => ['nullable', Rule::in($paymentPeriodicities)],
            'billing_type' => ['nullable', Rule::in($billingTypes)],
            'last_document_number' => ['nullable', 'string', 'max:30'],
            'last_payment_period' => ['nullable', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'payment_status' => ['nullable', Rule::in($paymentStatuses)],
            'is_current' => ['boolean'],
        ];
    }
}

