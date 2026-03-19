<?php

namespace App\Modules\Affiliates\Services;

use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function searchForAutocomplete(string $term, int $limit = 10, bool $serviconliOnly = false): Collection
    {
        $query = Affiliate::query()
            ->with(['socialSecurityProfile.eps'])
            ->searchByWords($term)
            ->limit($limit);

        if ($serviconliOnly) {
            $query->whereServiconliManaged();
        }

        return $query->get([
            'id', 'uuid', 'document_type', 'document_number',
            'first_name', 'second_name', 'last_name', 'second_last_name',
            'phone', 'whatsapp', 'status',
        ]);
    }

    public function create(array $data): Affiliate
    {
        // Fuente única de verdad operativa: PilaAffiliation (módulo Afiliaciones PILA). Desde aquí solo se crea el afiliado.
        $epsIdForBeneficiary = null;
        if (($data['patient_type'] ?? null) === 'beneficiario' && ! empty($data['eps_id'])) {
            $epsIdForBeneficiary = $data['eps_id'];
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

        $data['created_by'] = Auth::id();
        return DB::transaction(function () use ($data, $epsIdForBeneficiary) {
            $affiliate = Affiliate::create($data);

            // Beneficiario: perfil SS solo con EPS (del cotizante titular). Cotizante: sin perfil ni PILA aquí; se configura en Afiliaciones PILA.
            if (($data['patient_type'] ?? null) === 'beneficiario' && $epsIdForBeneficiary) {
                SocialSecurityProfile::create([
                    'affiliate_id' => $affiliate->id,
                    'eps_id' => $epsIdForBeneficiary,
                ]);
            }

            app(\App\Modules\Affiliates\Services\AffiliateTaskService::class)->createForNewAffiliate($affiliate);

            return $affiliate;
        });
    }

    public function update(Affiliate $affiliate, array $data): bool
    {
        // Fuente única de verdad operativa: PilaAffiliation. Desde aquí solo se actualizan datos del afiliado (identidad, contacto).
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

        return true;
    }
}
