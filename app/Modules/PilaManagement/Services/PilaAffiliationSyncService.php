<?php

namespace App\Modules\PilaManagement\Services;

use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\SocialSecurity\Models\ClientType;
use App\Modules\SocialSecurity\Models\ContributorType;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;
use App\Modules\SocialSecurity\Services\DueDateCalculator;

class PilaAffiliationSyncService
{
    /**
     * Sincroniza SocialSecurityProfile desde PilaAffiliation para evitar doble fuente de verdad.
     *
     * SocialSecurityProfile queda como capa de compatibilidad para módulos existentes.
     */
    public function syncToSocialSecurityProfile(Affiliate $affiliate, PilaAffiliation $pila): SocialSecurityProfile
    {
        $profile = $affiliate->socialSecurityProfile;

        $defaultClientTypeId = ClientType::where('code', 'SERVICONLI')->first()?->id;
        $defaultContributorTypeId = ContributorType::where('code', $pila->cotizanteType?->code ?? '01')->first()?->id;

        $payload = [
            'client_type_id' => $profile?->client_type_id ?? $defaultClientTypeId,
            'contributor_type_id' => $profile?->contributor_type_id ?? $defaultContributorTypeId,
            'eps_id' => $pila->eps_id,
            'afp_id' => $pila->afp_id,
            'arp_id' => $pila->arp_id,
            // Mantener compatibilidad: arp_risk_class como nivel 0..5 si existe risk_class
            'arp_risk_class' => $pila->riskClass ? (string) $pila->riskClass->level : null,
            'ccf_id' => $pila->ccf_id,
            'ibc' => $pila->ibc,
            'has_parafiscales' => (bool) $pila->pays_parafiscales,
        ];

        // payment_day: si el empleador trae payment_business_day lo usaremos en un feature posterior;
        // por ahora, si no hay payment_day, derivarlo del documento del afiliado como antes.
        $paymentDay = $profile?->payment_day;
        if ($paymentDay === null && $affiliate->document_number) {
            $paymentDay = app(DueDateCalculator::class)->paymentDayFromDocument($affiliate->document_number);
        }
        $payload['payment_day'] = $paymentDay;

        if ($profile) {
            $profile->update($payload);
            return $profile->refresh();
        }

        return SocialSecurityProfile::create(['affiliate_id' => $affiliate->id, ...$payload]);
    }
}

