<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\Eps;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;
use App\Modules\SocialSecurity\Models\Afp;
use App\Modules\SocialSecurity\Models\Arp;
use App\Modules\SocialSecurity\Models\ClientType;
use App\Modules\SocialSecurity\Models\ContributorType;

class AffiliateWithBeneficiarySeeder extends Seeder
{
    /**
     * Crea un afiliado cotizante con todos los datos, su perfil de seguridad social,
     * y un beneficiario (hijo menor) vinculado al cotizante.
     * Es idempotente: si ya existen por document_number, no vuelve a insertar.
     */
    public function run(): void
    {
        $eps = Eps::where('is_active', true)->first();
        if (! $eps) {
            $this->command->warn('No hay EPS activa. Ejecute EpsSeeder primero.');
            return;
        }

        $afpId = Afp::where('name', 'like', '%Porvenir%')->first()?->id;
        $arpId = Arp::where('name', 'like', '%Sura%')->first()?->id;
        $clientTypeId = ClientType::where('code', 'SERVICONLI')->first()?->id;
        $contributorTypeId = ContributorType::where('code', '01')->first()?->id;

        // 1. Afiliado cotizante (titular) — firstOrCreate para poder re-ejecutar el seeder
        $cotizante = Affiliate::firstOrCreate(
            [
                'document_type' => 'cc',
                'document_number' => '52987654',
            ],
            [
                'uuid' => Str::uuid()->toString(),
                'first_name' => 'Carlos',
                'second_name' => 'Alberto',
                'last_name' => 'Rodríguez',
                'second_last_name' => 'Pérez',
                'phone' => '3001234567',
                'phone_2' => '6017654321',
                'whatsapp' => '3001234567',
                'email' => 'carlos.rodriguez@example.com',
                'address' => 'Calle 45 # 12-34',
                'neighborhood' => 'Chapinero',
                'city' => 'Bogotá',
                'department' => 'Cundinamarca',
                'patient_type' => 'cotizante',
                'holder_id' => null,
                'relationship_type' => null,
                'birth_date' => '1985-03-15',
                'gender' => 'M',
                'status' => 'ACTIVO',
                'notes' => 'Afiliado de ejemplo creado por seeder.',
            ]
        );

        SocialSecurityProfile::firstOrCreate(
            ['affiliate_id' => $cotizante->id],
            [
                'client_type_id' => $clientTypeId,
                'contributor_type_id' => $contributorTypeId,
                'ibc' => 3500000.00,
                'eps_id' => $eps->id,
                'afp_id' => $afpId,
                'arp_id' => $arpId,
                'arp_risk_class' => '1',
                'ccf_id' => null,
                'payer_id' => null,
                'payment_operator_id' => null,
                'payment_day' => null,
                'payment_periodicity' => null,
                'has_parafiscales' => false,
                'accounting_registry_id' => null,
                'observations' => null,
            ]
        );

        // 2. Beneficiario (hijo menor del cotizante)
        $beneficiario = Affiliate::firstOrCreate(
            [
                'document_type' => 'ti',
                'document_number' => '10123456789',
            ],
            [
                'uuid' => Str::uuid()->toString(),
                'first_name' => 'María',
                'second_name' => 'Sofía',
                'last_name' => 'Rodríguez',
                'second_last_name' => 'Pérez',
                'phone' => '3001234567',
                'phone_2' => null,
                'whatsapp' => null,
                'email' => null,
                'address' => 'Calle 45 # 12-34',
                'neighborhood' => 'Chapinero',
                'city' => 'Bogotá',
                'department' => 'Cundinamarca',
                'patient_type' => 'beneficiario',
                'holder_id' => $cotizante->id,
                'relationship_type' => 'hijo_menor',
                'birth_date' => '2015-08-22',
                'gender' => 'F',
                'status' => 'ACTIVO',
                'notes' => 'Beneficiaria de ejemplo, hija del cotizante.',
            ]
        );

        SocialSecurityProfile::firstOrCreate(
            ['affiliate_id' => $beneficiario->id],
            [
                'client_type_id' => $clientTypeId,
                'contributor_type_id' => null,
                'ibc' => null,
                'eps_id' => $eps->id,
                'afp_id' => null,
                'arp_id' => null,
                'arp_risk_class' => null,
                'ccf_id' => null,
                'payer_id' => null,
                'payment_operator_id' => null,
                'payment_day' => null,
                'payment_periodicity' => null,
                'has_parafiscales' => false,
                'accounting_registry_id' => null,
                'observations' => 'EPS del titular.',
            ]
        );

        $this->command->info("Afiliado cotizante: {$cotizante->full_name} (ID: {$cotizante->id})");
        $this->command->info("Beneficiario: {$beneficiario->full_name} (ID: {$beneficiario->id}), titular ID: {$cotizante->id}");
    }
}
