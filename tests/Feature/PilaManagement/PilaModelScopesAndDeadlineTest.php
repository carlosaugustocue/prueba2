<?php

namespace Tests\Feature\PilaManagement;

use App\Modules\Affiliates\Enums\AffiliateStatus;
use App\Modules\Affiliates\Enums\DocumentType;
use App\Modules\Affiliates\Enums\PatientType;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaEmployer;
use App\Modules\PilaManagement\Services\DeadlineService;
use App\Modules\SocialSecurity\Services\DueDateCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilaModelScopesAndDeadlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_pila_affiliation_scopes_filter_correctly(): void
    {
        $employer = PilaEmployer::query()->create([
            'document_type' => 'NIT',
            'document_number' => '901776975',
            'check_digit' => '4',
            'name' => 'ACME SAS',
            'payment_business_day' => 5,
            'is_active' => true,
            'is_self_employed' => false,
        ]);

        $a1 = Affiliate::query()->create([
            'document_type' => DocumentType::CC,
            'document_number' => '10001',
            'first_name' => 'Juan',
            'last_name' => 'Uno',
            'patient_type' => PatientType::COTIZANTE,
            'status' => AffiliateStatus::ACTIVO,
        ]);

        $a2 = Affiliate::query()->create([
            'document_type' => DocumentType::CC,
            'document_number' => '10002',
            'first_name' => 'Juan',
            'last_name' => 'Dos',
            'patient_type' => PatientType::COTIZANTE,
            'status' => AffiliateStatus::ACTIVO,
        ]);

        PilaAffiliation::query()->create([
            'affiliate_id' => $a1->id,
            'employer_id' => $employer->id,
            'pila_operator' => 'arus',
            'payment_status' => 'overdue',
            'is_current' => true,
        ]);

        PilaAffiliation::query()->create([
            'affiliate_id' => $a2->id,
            'employer_id' => $employer->id,
            'pila_operator' => 'simple',
            'payment_status' => 'current',
            'is_current' => false,
        ]);

        $this->assertSame(1, PilaAffiliation::query()->current()->count());
        $this->assertSame(1, PilaAffiliation::query()->overdue()->count());
        $this->assertSame(1, PilaAffiliation::query()->byOperator('arus')->count());
        $this->assertSame(0, PilaAffiliation::query()->byOperator('nope')->count());
    }

    public function test_pila_employer_relations_and_scope_active(): void
    {
        $active = PilaEmployer::query()->create([
            'document_type' => 'NIT',
            'document_number' => '800123456',
            'name' => 'Activo SAS',
            'is_active' => true,
            'is_self_employed' => false,
        ]);

        $inactive = PilaEmployer::query()->create([
            'document_type' => 'NIT',
            'document_number' => '800123457',
            'name' => 'Inactivo SAS',
            'is_active' => false,
            'is_self_employed' => false,
        ]);

        $affiliate = Affiliate::query()->create([
            'document_type' => DocumentType::CC,
            'document_number' => '20001',
            'first_name' => 'Maria',
            'last_name' => 'Perez',
            'patient_type' => PatientType::COTIZANTE,
            'status' => AffiliateStatus::ACTIVO,
        ]);

        PilaAffiliation::query()->create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => $active->id,
            'is_current' => true,
        ]);

        $this->assertSame(1, PilaEmployer::query()->active()->count());
        $this->assertSame(1, $active->affiliations()->count());
        $this->assertSame(1, $active->affiliates()->count());
    }

    public function test_deadline_accessor_uses_deadline_service_for_due_date(): void
    {
        $employer = PilaEmployer::query()->create([
            'document_type' => 'NIT',
            'document_number' => '901776975',
            'name' => 'ACME SAS',
            'payment_business_day' => 2,
            'is_active' => true,
            'is_self_employed' => false,
        ]);

        $affiliate = Affiliate::query()->create([
            'document_type' => DocumentType::CC,
            'document_number' => '30001',
            'first_name' => 'Pedro',
            'last_name' => 'Gomez',
            'patient_type' => PatientType::COTIZANTE,
            'status' => AffiliateStatus::ACTIVO,
        ]);

        $affiliation = PilaAffiliation::query()->create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => $employer->id,
            'last_payment_period' => '202601',
            'is_current' => true,
        ]);

        $expected = app(DeadlineService::class)->dueDateForPeriodByPaymentDay(2026, 1, 2)->toDateString();
        $this->assertNotNull($affiliation->deadline);
        $this->assertSame($expected, $affiliation->deadline->toDateString());
    }

    public function test_due_date_calculator_delegates_to_deadline_service(): void
    {
        $calc = app(DueDateCalculator::class);
        $svc = app(DeadlineService::class);

        $doc = '901776975-4';

        $this->assertSame(
            $svc->paymentBusinessDayFromDocument($doc),
            $calc->paymentDayFromDocument($doc)
        );

        $this->assertSame(
            $svc->dueDateForPeriod(2026, 1, $doc)->toDateString(),
            $calc->dueDateForPeriod(2026, 1, $doc)->toDateString()
        );
    }
}

