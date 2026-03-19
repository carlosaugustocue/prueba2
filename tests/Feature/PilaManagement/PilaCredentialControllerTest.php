<?php

namespace Tests\Feature\PilaManagement;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaCredential;
use App\Modules\PilaManagement\Models\PilaEmployer;
use App\Modules\PilaManagement\Models\PortalCredential;
use App\Modules\PilaManagement\Enums\CredentialAction;
use App\Modules\PilaManagement\Services\CredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PilaCredentialControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => null,
            'permissions' => null,
        ]);

        $user = User::query()->create([
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'social_security_role' => null,
            'phone' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }

    private function authAgent(): User
    {
        $role = Role::query()->create([
            'name' => 'agent',
            'display_name' => 'Agent',
            'description' => null,
            'permissions' => null,
        ]);

        $user = User::query()->create([
            'name' => 'Agent Test',
            'email' => 'agent@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'social_security_role' => null,
            'phone' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }

    /** @test */
    public function upsert_pila_credential_creates_audit_log()
    {
        $this->authAdmin();

        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '200',
            'first_name' => 'Juan',
            'last_name' => 'Test',
            'patient_type' => 'cotizante',
        ]);

        $employer = PilaEmployer::create([
            'document_type' => 'nit',
            'document_number' => '900123',
            'name' => 'Empresa Test',
            'payment_business_day' => 5,
            'is_active' => true,
            'is_self_employed' => false,
        ]);

        $affiliation = PilaAffiliation::create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => $employer->id,
            'ibc' => 1000,
            'pila_operator' => 'arus',
            'is_current' => true,
        ]);

        $svc = app(CredentialService::class);

        $this->postJson("/pila/affiliations/{$affiliation->id}/credentials/pila", [
            'operator' => 'simple',
            'username' => 'pilauser',
            'password' => '123456',
            'is_active' => true,
        ])->assertOk();

        $this->assertDatabaseHas('pila_credentials', [
            'employer_id' => $employer->id,
            'operator' => 'simple',
            'username' => 'pilauser',
        ]);

        $credentialId = PilaCredential::query()
            ->where('employer_id', $employer->id)
            ->where('operator', 'simple')
            ->value('id');

        $this->assertDatabaseHas('credential_audit_logs', [
            'credential_kind' => 'pila',
            'credential_id' => $credentialId,
            'action' => CredentialAction::CREATED->value,
        ]);

        // Asegura que realmente se cifró (no se guarda el texto plano).
        $stored = PilaCredential::query()->findOrFail($credentialId);
        $this->assertNotSame('123456', $stored->getRawOriginal('password_encrypted'));
    }

    /** @test */
    public function upsert_portal_not_applicable_creates_audit_log()
    {
        $this->authAdmin();

        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '201',
            'first_name' => 'Maria',
            'last_name' => 'Test',
            'patient_type' => 'cotizante',
        ]);

        $employer = PilaEmployer::create([
            'document_type' => 'nit',
            'document_number' => '900124',
            'name' => 'Empresa Test 2',
            'payment_business_day' => 5,
            'is_active' => true,
            'is_self_employed' => false,
        ]);

        $affiliation = PilaAffiliation::create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => $employer->id,
            'ibc' => 1000,
            'pila_operator' => 'simple',
            'is_current' => true,
        ]);

        $this->postJson("/pila/affiliations/{$affiliation->id}/credentials/portal", [
            'entity_type' => 'EPS',
            'is_not_applicable' => true,
            'username' => null,
            'password' => null,
            'is_active' => true,
        ])->assertOk();

        $this->assertDatabaseHas('portal_credentials', [
            'affiliate_id' => $affiliate->id,
            'entity_type' => 'EPS',
            'is_not_applicable' => true,
        ]);

        $credentialId = PortalCredential::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('entity_type', 'EPS')
            ->value('id');

        $this->assertDatabaseHas('credential_audit_logs', [
            'credential_kind' => 'portal',
            'credential_id' => $credentialId,
            'action' => CredentialAction::CREATED->value,
        ]);
    }

    /** @test */
    public function update_pila_credential_updates_password_and_audit_log()
    {
        $this->authAdmin();

        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '202',
            'first_name' => 'Carlos',
            'last_name' => 'Test',
            'patient_type' => 'cotizante',
        ]);

        $employer = PilaEmployer::create([
            'document_type' => 'nit',
            'document_number' => '900125',
            'name' => 'Empresa Test 3',
            'payment_business_day' => 5,
            'is_active' => true,
            'is_self_employed' => false,
        ]);

        $affiliation = PilaAffiliation::create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => $employer->id,
            'ibc' => 1000,
            'pila_operator' => 'simple',
            'is_current' => true,
        ]);

        $svc = app(CredentialService::class);

        $credential = PilaCredential::query()->create([
            'employer_id' => $employer->id,
            'operator' => 'simple',
            'username' => 'olduser',
            'password_encrypted' => $svc->encrypt('oldpass'),
            'is_active' => true,
            'password_updated_at' => now(),
        ]);

        $this->postJson("/pila/credentials/pila/{$credential->id}/update", [
            'username' => 'newuser',
            'password' => 'newpass',
            'is_active' => true,
        ])->assertOk();

        $this->assertDatabaseHas('pila_credentials', [
            'id' => $credential->id,
            'username' => 'newuser',
        ]);

        $this->assertDatabaseHas('credential_audit_logs', [
            'credential_kind' => 'pila',
            'credential_id' => $credential->id,
            'action' => CredentialAction::UPDATED->value,
        ]);
    }

    /** @test */
    public function audit_logs_endpoint_returns_logs_for_admin()
    {
        $this->authAdmin();

        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '300',
            'first_name' => 'Ana',
            'last_name' => 'Test',
            'patient_type' => 'cotizante',
        ]);

        $employer = PilaEmployer::create([
            'document_type' => 'nit',
            'document_number' => '900200',
            'name' => 'Empresa Test 4',
            'payment_business_day' => 5,
            'is_active' => true,
            'is_self_employed' => false,
        ]);

        $affiliation = PilaAffiliation::create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => $employer->id,
            'ibc' => 1000,
            'pila_operator' => 'simple',
            'is_current' => true,
        ]);

        $svc = app(CredentialService::class);
        $credential = PilaCredential::query()->create([
            'employer_id' => $employer->id,
            'operator' => 'simple',
            'username' => 'user1',
            'password_encrypted' => $svc->encrypt('secret123'),
            'is_active' => true,
            'password_updated_at' => now(),
        ]);

        // Genera auditoría por "reveal"
        $this->postJson("/pila/credentials/pila/{$credential->id}/reveal", [])
            ->assertOk();

        $this->getJson("/pila/affiliations/{$affiliation->id}/credentials/audit-logs")
            ->assertOk()
            ->assertJsonStructure([
                'logs' => [],
            ]);

        $this->assertDatabaseHas('credential_audit_logs', [
            'credential_kind' => 'pila',
            'credential_id' => $credential->id,
            'action' => CredentialAction::VIEWED->value,
        ]);
    }

    /** @test */
    public function audit_logs_endpoint_denies_for_agent()
    {
        $this->authAgent();

        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '301',
            'first_name' => 'Luis',
            'last_name' => 'Test',
            'patient_type' => 'cotizante',
        ]);

        $employer = PilaEmployer::create([
            'document_type' => 'nit',
            'document_number' => '900201',
            'name' => 'Empresa Test 5',
            'payment_business_day' => 5,
            'is_active' => true,
            'is_self_employed' => false,
        ]);

        $affiliation = PilaAffiliation::create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => $employer->id,
            'ibc' => 1000,
            'pila_operator' => 'simple',
            'is_current' => true,
        ]);

        $this->getJson("/pila/affiliations/{$affiliation->id}/credentials/audit-logs")
            ->assertStatus(403);
    }

    /** @test */
    public function show_page_audit_logs_is_empty_when_affiliation_has_no_credentials()
    {
        $this->authAdmin();

        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '402',
            'first_name' => 'Sofia',
            'last_name' => 'Test',
            'patient_type' => 'cotizante',
        ]);

        // Cero credenciales: sin employer_id => no hay pila_credentials y no hay portal_credentials por defecto.
        $affiliation = PilaAffiliation::create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => null,
            'pila_operator' => 'simple',
            'is_current' => true,
        ]);

        $this->get("/pila/affiliations/{$affiliation->id}")
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) {
                $page->component('PilaAffiliations/Show')
                    // El modal usa `audit_logs.data`; debe venir vacío.
                    ->where('audit_logs.data', []);
            });
    }

    /** @test */
    public function show_page_audit_logs_is_null_for_non_admin()
    {
        $this->authAgent();

        $affiliate = Affiliate::create([
            'document_type' => 'cc',
            'document_number' => '403',
            'first_name' => 'Valentina',
            'last_name' => 'Test',
            'patient_type' => 'cotizante',
        ]);

        $affiliation = PilaAffiliation::create([
            'affiliate_id' => $affiliate->id,
            'employer_id' => null,
            'pila_operator' => 'simple',
            'is_current' => true,
        ]);

        $this->get("/pila/affiliations/{$affiliation->id}")
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) {
                $page->component('PilaAffiliations/Show')
                    ->where('audit_logs', null);
            });
    }
}

