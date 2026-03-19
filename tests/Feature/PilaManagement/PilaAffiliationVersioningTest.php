<?php

namespace Tests\Feature\PilaManagement;

use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Services\PilaAffiliationVersioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PilaAffiliationVersioningTest extends TestCase
{
    use RefreshDatabase;

    protected PilaAffiliationVersioningService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PilaAffiliationVersioningService::class);
    }

    /** @test */
    public function can_create_initial_affiliation_when_none_exists()
    {
        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '123',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'patient_type' => 'cotizante',
        ]);

        $affiliation = $this->service->createInitial($affiliate, [
            'employer_id' => null,
            'eps_id' => null,
            'ibc' => 1500000,
        ]);

        $this->assertInstanceOf(PilaAffiliation::class, $affiliation);
        $this->assertTrue($affiliation->is_current);

        $this->assertDatabaseHas('pila_affiliations', [
            'id' => $affiliation->id,
            'affiliate_id' => $affiliate->id,
            'is_current' => true,
        ]);
    }

    /** @test */
    public function creating_initial_affiliation_when_current_exists_throws_exception()
    {
        $this->expectException(RuntimeException::class);

        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '124',
            'first_name' => 'Luis',
            'last_name' => 'Gómez',
            'patient_type' => 'cotizante',
        ]);

        PilaAffiliation::create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => null,
            'eps_id' => null,
            'ibc' => 1000000,
            'is_current' => true,
        ]);

        $this->service->createInitial($affiliate, [
            'employer_id' => null,
            'eps_id' => null,
            'ibc' => 2000000,
        ]);
    }

    /** @test */
    public function change_affiliation_updates_current_affiliation_fields()
    {
        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '125',
            'first_name' => 'Carlos',
            'last_name' => 'Lopez',
            'patient_type' => 'cotizante',
        ]);

        $current = PilaAffiliation::create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => null,
            'eps_id' => null,
            'ibc' => 1000000,
            'pila_operator' => 'simple',
            'is_current' => true,
        ]);

        $new = $this->service->changeAffiliation($affiliate, [
            'ibc' => 2000000,
        ]);

        $this->assertTrue($current->fresh()->is_current);
        $this->assertTrue($new->is_current);
        $this->assertEquals(2000000, $new->ibc);

        $this->assertSame('simple', $new->pila_operator);
    }

    /** @test */
    public function change_affiliation_without_existing_current_throws_exception()
    {
        $this->expectException(RuntimeException::class);

        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '126',
            'first_name' => 'Ana',
            'last_name' => 'Ríos',
            'patient_type' => 'cotizante',
        ]);

        $this->service->changeAffiliation($affiliate, [
            'employer_id' => 11,
        ]);
    }
}

