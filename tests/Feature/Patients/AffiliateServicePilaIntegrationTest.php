<?php

namespace Tests\Feature\Patients;

use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\Eps;
use App\Modules\Affiliates\Services\AffiliateService;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateServicePilaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function create_cotizante_does_not_create_pila_affiliation_or_social_security_profile()
    {
        /** @var AffiliateService $service */
        $service = $this->app->make(AffiliateService::class);

        $affiliate = $service->create([
            'document_type' => 'cc',
            'document_number' => '1000001',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'patient_type' => 'cotizante',
        ]);

        $this->assertInstanceOf(Affiliate::class, $affiliate);
        $this->assertDatabaseHas('affiliates', [
            'id' => $affiliate->id,
            'document_number' => '1000001',
        ]);

        $this->assertDatabaseMissing('pila_affiliations', ['affiliate_id' => $affiliate->id]);
        $this->assertDatabaseMissing('social_security_profiles', ['affiliate_id' => $affiliate->id]);
    }

    /** @test */
    public function create_beneficiary_creates_social_security_profile_with_eps_from_holder()
    {
        /** @var AffiliateService $service */
        $service = $this->app->make(AffiliateService::class);

        $this->seed(\Database\Seeders\EpsSeeder::class);
        $eps = Eps::firstOrFail();

        $holder = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '9000001',
            'first_name' => 'Titular',
            'last_name' => 'PILA',
            'patient_type' => 'cotizante',
        ]);

        SocialSecurityProfile::create([
            'affiliate_id' => $holder->id,
            'eps_id' => $eps->id,
        ]);

        $beneficiary = $service->create([
            'document_type' => 'cc',
            'document_number' => '2000001',
            'first_name' => 'Ana',
            'last_name' => 'López',
            'patient_type' => 'beneficiario',
            'holder_id' => $holder->id,
            'eps_id' => $eps->id,
        ]);

        $this->assertInstanceOf(Affiliate::class, $beneficiary);

        $this->assertDatabaseHas('social_security_profiles', [
            'affiliate_id' => $beneficiary->id,
            'eps_id' => $eps->id,
        ]);

        $this->assertDatabaseMissing('pila_affiliations', ['affiliate_id' => $beneficiary->id]);
    }
}

