<?php

namespace App\Modules\Patients\Services;

use App\Modules\Patients\Models\Affiliate;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class AffiliateService
{
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Affiliate::query()->with(['socialSecurityProfile.eps']);

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
            'eps_id' => $data['eps_id'] ?? null,
            'afp_name' => $data['afp_name'] ?? null,
            'arp_name' => $data['arp_name'] ?? null,
            'arp_risk_class' => $data['arp_risk_class'] ?? null,
        ];
        unset($data['eps_id'], $data['afp_name'], $data['arp_name'], $data['arp_risk_class']);

        $data['created_by'] = Auth::id();
        $affiliate = Affiliate::create($data);

        if (array_filter($ss)) {
            SocialSecurityProfile::create([
                'affiliate_id' => $affiliate->id,
                'client_type' => 'SERVICONLI',
                'contributor_type' => '01',
                'eps_id' => $ss['eps_id'],
                'afp_name' => $ss['afp_name'],
                'arp_name' => $ss['arp_name'],
                'arp_risk_class' => $ss['arp_risk_class'],
            ]);
        }

        return $affiliate;
    }

    public function update(Affiliate $affiliate, array $data): bool
    {
        $ss = [
            'eps_id' => $data['eps_id'] ?? null,
            'afp_name' => $data['afp_name'] ?? null,
            'arp_name' => $data['arp_name'] ?? null,
            'arp_risk_class' => $data['arp_risk_class'] ?? null,
        ];
        unset($data['eps_id'], $data['afp_name'], $data['arp_name'], $data['arp_risk_class']);

        $data['updated_by'] = Auth::id();
        $affiliate->update($data);

        $profile = $affiliate->socialSecurityProfile;
        if ($profile) {
            $profile->update($ss);
        } elseif (array_filter($ss)) {
            SocialSecurityProfile::create([
                'affiliate_id' => $affiliate->id,
                'client_type' => 'SERVICONLI',
                'contributor_type' => '01',
                'eps_id' => $ss['eps_id'],
                'afp_name' => $ss['afp_name'],
                'arp_name' => $ss['arp_name'],
                'arp_risk_class' => $ss['arp_risk_class'],
            ]);
        }

        return true;
    }
}
