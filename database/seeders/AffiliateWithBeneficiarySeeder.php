<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\Patients\Models\Eps;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;

class AffiliateWithBeneficiarySeeder extends Seeder
{
    /**
     * Crea un afiliado cotizante con todos los datos, su perfil de seguridad social,
     * y un beneficiario (hijo menor) vinculado al cotizante.
     */
    public function run(): void
    {
        $eps = Eps::where('is_active', true)->first();
        if (! $eps) {
            $this->command->warn('No hay EPS activa. Ejecute EpsSeeder primero.');
            return;
        }

        // 1. Afiliado cotizante (titular)
        $cotizante = Affiliate::create([
            'uuid' => Str::uuid()->toString(),
            'document_type' => 'cc',
            'document_number' => '52987654',
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
        ]);

        SocialSecurityProfile::create([
            'affiliate_id' => $cotizante->id,
            'client_type' => 'SERVICONLI',
            'contributor_type' => '01',
            'ibc' => 3500000.00,
            'eps_id' => $eps->id,
            'afp_name' => 'Porvenir',
            'arp_name' => 'Sura',
            'arp_risk_class' => '1',
            'ccf_name' => null,
            'payer_id' => null,
            'payment_operator' => null,
            'payment_day' => null,
            'payment_periodicity' => null,
            'has_parafiscales' => false,
            'accounting_registry' => null,
            'observations' => null,
        ]);

        // 2. Beneficiario (hijo menor del cotizante)
        $beneficiario = Affiliate::create([
            'uuid' => Str::uuid()->toString(),
            'document_type' => 'ti',
            'document_number' => '10123456789',
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
        ]);

        SocialSecurityProfile::create([
            'affiliate_id' => $beneficiario->id,
            'client_type' => 'SERVICONLI',
            'contributor_type' => null,
            'ibc' => null,
            'eps_id' => $eps->id,
            'afp_name' => null,
            'arp_name' => null,
            'arp_risk_class' => null,
            'ccf_name' => null,
            'payer_id' => null,
            'payment_operator' => null,
            'payment_day' => null,
            'payment_periodicity' => null,
            'has_parafiscales' => false,
            'accounting_registry' => null,
            'observations' => 'EPS del titular.',
        ]);

        $this->command->info("Afiliado cotizante creado: {$cotizante->full_name} (ID: {$cotizante->id})");
        $this->command->info("Beneficiario creado: {$beneficiario->full_name} (ID: {$beneficiario->id}), titular ID: {$cotizante->id}");
    }
}
