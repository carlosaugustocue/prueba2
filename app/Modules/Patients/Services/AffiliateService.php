<?php

namespace App\Modules\Patients\Services;

use App\Modules\Patients\Models\Affiliate;
use App\Modules\SocialSecurity\Models\ClientType;
use App\Modules\SocialSecurity\Models\ContributorType;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;
use App\Modules\SocialSecurity\Services\DueDateCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class AffiliateService
{
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Affiliate::query()->with([
            'socialSecurityProfile.eps',
            'socialSecurityProfile.clientType',
            'socialSecurityProfile.contributorType',
            'socialSecurityProfile.afp',
            'socialSecurityProfile.arp',
            'socialSecurityProfile.ccf',
            'socialSecurityProfile.paymentOperator',
            'socialSecurityProfile.accountingRegistry',
        ]);

        if (! empty($filters['search'])) {
            $query->searchByWords($filters['search']);
        }

        if (! empty($filters['eps_id'])) {
            $query->whereHas('socialSecurityProfile', fn ($q) => $q->where('eps_id', $filters['eps_id']));
        }

        if (! empty($filters['patient_type'])) {
            $query->where('patient_type', $filters['patient_type']);
        }

        return $query->orderBy('first_name')->orderBy('last_name')->paginate($perPage)->withQueryString();
    }

    public function searchForAutocomplete(string $term, int $limit = 10): Collection
    {
        return Affiliate::query()
            ->with(['socialSecurityProfile'])
            ->searchByWords($term)
            ->limit($limit)
            ->get([
                'id', 'uuid', 'document_type', 'document_number',
                'first_name', 'second_name', 'last_name', 'second_last_name',
                'phone', 'whatsapp', 'status',
            ]);
    }

    public function create(array $data): Affiliate
    {
        $ss = [
            'client_type_id' => ! empty($data['client_type_id']) ? $data['client_type_id'] : (ClientType::where('code', 'SERVICONLI')->first()?->id),
            'contributor_type_id' => ! empty($data['contributor_type_id']) ? $data['contributor_type_id'] : (ContributorType::where('code', '01')->first()?->id),
            'eps_id' => $data['eps_id'] ?? null,
            'afp_id' => ! empty($data['afp_id']) ? $data['afp_id'] : null,
            'arp_id' => ! empty($data['arp_id']) ? $data['arp_id'] : null,
            'arp_risk_class' => $data['arp_risk_class'] ?? null,
            'ccf_id' => ! empty($data['ccf_id']) ? $data['ccf_id'] : null,
            'payer_id' => ! empty($data['payer_id']) ? $data['payer_id'] : null,
            'payment_operator_id' => ! empty($data['payment_operator_id']) ? $data['payment_operator_id'] : null,
            'accounting_registry_id' => ! empty($data['accounting_registry_id']) ? $data['accounting_registry_id'] : null,
            'ibc' => $data['ibc'] ?? null,
            'payment_day' => isset($data['payment_day']) && $data['payment_day'] !== '' ? (int) $data['payment_day'] : null,
            'payment_periodicity' => $data['payment_periodicity'] ?? null,
            'has_parafiscales' => (bool) ($data['has_parafiscales'] ?? false),
            'observations' => $data['observations'] ?? null,
        ];
        unset(
            $data['eps_id'],
            $data['client_type_id'],
            $data['contributor_type_id'],
            $data['afp_id'],
            $data['arp_id'],
            $data['arp_risk_class'],
            $data['ccf_id'],
            $data['payer_id'],
            $data['payment_operator_id'],
            $data['accounting_registry_id'],
            $data['ibc'],
            $data['payment_day'],
            $data['payment_periodicity'],
            $data['has_parafiscales'],
            $data['observations']
        );

        $data['created_by'] = Auth::id();
        $affiliate = Affiliate::create($data);

        if (array_filter($ss, fn ($v) => $v !== null && $v !== '')) {
            if ($ss['payment_day'] === null && $affiliate->document_number) {
                $ss['payment_day'] = app(DueDateCalculator::class)->paymentDayFromDocument($affiliate->document_number);
            }
            SocialSecurityProfile::create(array_merge(
                ['affiliate_id' => $affiliate->id],
                array_filter($ss, fn ($v) => $v !== null && $v !== '')
            ));
        }

        return $affiliate;
    }

    public function update(Affiliate $affiliate, array $data): bool
    {
        $ss = [
            'eps_id' => $data['eps_id'] ?? null,
            'afp_id' => ! empty($data['afp_id']) ? $data['afp_id'] : null,
            'arp_id' => ! empty($data['arp_id']) ? $data['arp_id'] : null,
            'arp_risk_class' => $data['arp_risk_class'] ?? null,
            'ccf_id' => ! empty($data['ccf_id']) ? $data['ccf_id'] : null,
            'payer_id' => array_key_exists('payer_id', $data) ? ($data['payer_id'] ?: null) : null,
            'payment_operator_id' => ! empty($data['payment_operator_id']) ? $data['payment_operator_id'] : null,
            'accounting_registry_id' => ! empty($data['accounting_registry_id']) ? $data['accounting_registry_id'] : null,
            'ibc' => array_key_exists('ibc', $data) ? ($data['ibc'] !== '' && $data['ibc'] !== null ? $data['ibc'] : null) : null,
            'payment_day' => array_key_exists('payment_day', $data) && $data['payment_day'] !== '' && $data['payment_day'] !== null ? (int) $data['payment_day'] : null,
            'payment_periodicity' => $data['payment_periodicity'] ?? null,
            'has_parafiscales' => (bool) ($data['has_parafiscales'] ?? false),
            'observations' => $data['observations'] ?? null,
        ];
        if (array_key_exists('client_type_id', $data)) {
            $ss['client_type_id'] = ! empty($data['client_type_id']) ? $data['client_type_id'] : null;
        }
        if (array_key_exists('contributor_type_id', $data)) {
            $ss['contributor_type_id'] = ! empty($data['contributor_type_id']) ? $data['contributor_type_id'] : null;
        }
        unset(
            $data['eps_id'],
            $data['client_type_id'],
            $data['contributor_type_id'],
            $data['afp_id'],
            $data['arp_id'],
            $data['arp_risk_class'],
            $data['ccf_id'],
            $data['payer_id'],
            $data['payment_operator_id'],
            $data['accounting_registry_id'],
            $data['ibc'],
            $data['payment_day'],
            $data['payment_periodicity'],
            $data['has_parafiscales'],
            $data['observations']
        );

        $data['updated_by'] = Auth::id();
        $affiliate->update($data);

        if ($ss['payment_day'] === null && $affiliate->document_number) {
            $ss['payment_day'] = app(DueDateCalculator::class)->paymentDayFromDocument($affiliate->document_number);
        }

        $profile = $affiliate->socialSecurityProfile;
        if ($profile) {
            $profile->update($ss);
        } elseif (array_filter($ss, fn ($v) => $v !== null && $v !== '')) {
            $defaultClientTypeId = ClientType::where('code', 'SERVICONLI')->first()?->id;
            $defaultContributorTypeId = ContributorType::where('code', '01')->first()?->id;
            SocialSecurityProfile::create([
                'affiliate_id' => $affiliate->id,
                'client_type_id' => $ss['client_type_id'] ?? $defaultClientTypeId,
                'contributor_type_id' => $ss['contributor_type_id'] ?? $defaultContributorTypeId,
                'eps_id' => $ss['eps_id'],
                'afp_id' => $ss['afp_id'],
                'arp_id' => $ss['arp_id'],
                'arp_risk_class' => $ss['arp_risk_class'],
                'ccf_id' => $ss['ccf_id'],
                'payer_id' => $ss['payer_id'] ?? null,
                'payment_operator_id' => $ss['payment_operator_id'],
                'accounting_registry_id' => $ss['accounting_registry_id'] ?? null,
                'ibc' => $ss['ibc'] ?? null,
                'payment_day' => $ss['payment_day'] ?? null,
                'payment_periodicity' => $ss['payment_periodicity'] ?? null,
                'has_parafiscales' => $ss['has_parafiscales'] ?? false,
                'observations' => $ss['observations'] ?? null,
            ]);
        }

        return true;
    }
}
