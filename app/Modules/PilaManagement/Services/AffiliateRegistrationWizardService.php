<?php

namespace App\Modules\PilaManagement\Services;

use App\Modules\Affiliates\Services\AffiliateService;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaEmployer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AffiliateRegistrationWizardService
{
    public function __construct(private readonly AffiliateService $affiliateService)
    {
    }

    /**
     * Registra un afiliado y su afiliación PILA en una sola transacción DB.
     *
     * Orden de persistencia:
     *  1) Employer (findOrCreate por NIT)
     *  2) Affiliate
     *  3) PilaAffiliation (is_current=true)
     */
    public function registerCotizanteWithPila(array $data): PilaAffiliation
    {
        return DB::transaction(function () use ($data) {
            $employer = $this->findOrCreateEmployerFromNit($data);

            $affiliate = $this->affiliateService->create($this->buildAffiliatePayload($data));

            $pilaPayload = [
                'affiliate_id' => $affiliate->id,
                'employer_id' => $employer->id,
                'cotizante_type_id' => $data['cotizante_type_id'] ?? null,
                'pila_operator' => $data['pila_operator'] ?? null,
                'last_novelty_type' => $data['last_novelty_type'] ?? null,
                'last_novelty_date' => $data['last_novelty_date'] ?? null,
                'ibc' => $data['ibc'],
                'pays_parafiscales' => (bool) ($data['pays_parafiscales'] ?? false),
                'self_employed' => (bool) ($data['self_employed'] ?? false),
                'risk_class_id' => $data['risk_class_id'] ?? null,
                'eps_id' => $data['eps_id'] ?? null,
                'afp_id' => $data['afp_id'] ?? null,
                'arp_id' => $data['arp_id'] ?? null,
                'ccf_id' => $data['ccf_id'] ?? null,
                'payment_periodicity' => $data['payment_periodicity'] ?? null,
                'billing_type' => $data['billing_type'] ?? null,
                'last_document_number' => $data['last_document_number'] ?? null,
                'last_payment_period' => $data['last_payment_period'] ?? null,
                'payment_status' => $data['payment_status'] ?? null,
                'is_current' => true,
            ];

            $pilaAffiliation = PilaAffiliation::query()->create($pilaPayload);

            // Compatibilidad legacy: crea/sincroniza SocialSecurityProfile desde PILA.
            app(PilaAffiliationSyncService::class)
                ->syncToSocialSecurityProfile($affiliate, $pilaAffiliation);

            return $pilaAffiliation;
        });
    }

    private function buildAffiliatePayload(array $data): array
    {
        return [
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'],
            'document_issue_date' => $data['document_issue_date'] ?? null,
            'first_name' => $data['first_name'],
            'second_name' => $data['second_name'] ?? null,
            'last_name' => $data['last_name'],
            'second_last_name' => $data['second_last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'phone_2' => $data['phone_2'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'city' => $data['city'] ?? null,
            'department' => $data['department'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'status' => $data['status'] ?? 'ACTIVO',
            'patient_type' => 'cotizante',
            'holder_id' => null,
            'relationship_type' => null,
            'eps_id' => $data['eps_id'] ?? null, // solo usado si fuera beneficiario; para cotizante no crea perfil acá.
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function findOrCreateEmployerFromNit(array $data): PilaEmployer
    {
        $nitRaw = trim((string) ($data['employer_nit'] ?? ''));
        if ($nitRaw === '') {
            throw new InvalidArgumentException('employer_nit es requerido.');
        }

        [$nitBase, $nitDv] = $this->splitDocumentNumberForEmployerLikeExcel($nitRaw);
        if ($nitBase === '') {
            throw new InvalidArgumentException('El NIT del empleador no es válido.');
        }

        // En esta app se modela el tipo para empleadores como "NIT" (tal cual Excel).
        $documentType = 'NIT';

        $existing = PilaEmployer::query()
            ->where('document_type', $documentType)
            ->where('document_number', $nitBase)
            ->first();

        $payload = [
            'check_digit' => $nitDv,
            'name' => $data['employer_name'] ?? ($data['employer_nit'] ?? $nitBase),
            'address' => $data['employer_address'] ?? null,
            'city' => $data['employer_city'] ?? null,
            'department' => $data['employer_department'] ?? null,
            'phone' => $data['employer_phone'] ?? null,
            'email' => $data['employer_email'] ?? null,
            'payment_business_day' => $data['employer_payment_business_day'] ?? null,
            'is_self_employed' => (bool) ($data['employer_is_self_employed'] ?? false),
            'is_active' => (bool) ($data['employer_is_active'] ?? true),
            'notes' => null,
        ];

        if ($existing) {
            $existing->update($payload);
            return $existing;
        }

        return PilaEmployer::query()->create([
            'document_type' => $documentType,
            'document_number' => $nitBase,
            ...$payload,
        ]);
    }

    /**
     * Replicamos la lógica del import (NIT puede venir como "901776975-4").
     *
     * @return array{0: string, 1: string|null} [document_number, check_digit]
     */
    private function splitDocumentNumberForEmployerLikeExcel(string $raw): array
    {
        $s = trim((string) $raw);

        if ($s === '') {
            return ['', null];
        }

        if (str_contains($s, '-')) {
            $parts = explode('-', $s, 2);
            $base = preg_replace('/\D/', '', $parts[0] ?? '');
            $dv = preg_replace('/\D/', '', $parts[1] ?? '');
            $dv = $dv !== '' ? substr($dv, -1) : null;
            return [$base, $dv];
        }

        $digitsOnly = preg_replace('/\D/', '', $s);
        if ($digitsOnly === '') {
            return ['', null];
        }

        return [$digitsOnly, null];
    }
}

