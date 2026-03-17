<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pila_employers', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 10);
            $table->string('document_number', 20);
            $table->char('check_digit', 1)->nullable();
            $table->string('name', 200);
            $table->string('address', 200)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();

            // Día hábil según Decreto 1990/2016: 2..16 (no almacenar fecha límite, solo el día hábil)
            $table->unsignedTinyInteger('payment_business_day')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_self_employed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['document_type', 'document_number']);
            $table->index(['city', 'department']);
            $table->index('payment_business_day');
        });

        Schema::create('pila_cotizante_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('name', 200);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pila_social_entities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10); // AFP, ARL, CCF, EPS, SENA, ICBF
            $table->string('code', 20);
            $table->string('name', 200);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'code']);
            $table->index(['type', 'name']);
        });

        Schema::create('pila_risk_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level'); // 0..5
            $table->string('class_name', 5)->nullable(); // I..V o null si 0
            $table->string('description', 100);
            $table->decimal('rate', 7, 5); // 0.00522 etc
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['level']);
        });

        Schema::create('pila_affiliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->unique()->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('employer_id')->nullable()->constrained('pila_employers')->nullOnDelete();

            // Tipo cotizante PILA (01, 03, 57, 59, ...)
            $table->foreignId('cotizante_type_id')->nullable()->constrained('pila_cotizante_types')->nullOnDelete();

            // Operación PILA
            $table->string('pila_operator', 30)->nullable(); // arus, simple, ...
            $table->string('last_novelty_type', 10)->nullable(); // ING, RET, etc
            $table->date('last_novelty_date')->nullable();

            // Datos laborales
            $table->decimal('ibc', 12, 2)->nullable();
            $table->boolean('pays_parafiscales')->default(false);
            $table->boolean('self_employed')->default(false);

            // Entidades (FK unificada)
            $table->foreignId('arl_entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();
            $table->foreignId('risk_class_id')->nullable()->constrained('pila_risk_classes')->nullOnDelete();
            $table->foreignId('ccf_entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();
            $table->foreignId('eps_entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();
            $table->foreignId('afp_entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();

            // Facturación y seguimiento
            $table->string('payment_periodicity', 20)->nullable(); // vencido/actual (catálogo luego)
            $table->string('billing_type', 30)->nullable(); // recibo_caja/factura_electronica (catálogo luego)
            $table->string('last_document_number', 30)->nullable();
            $table->char('last_payment_period', 6)->nullable(); // AAAAMM
            $table->string('payment_status', 20)->nullable(); // current/overdue/anticipated (catálogo luego)

            // Metadatos
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['employer_id', 'pila_operator']);
            $table->index(['payment_status', 'last_payment_period']);
        });

        Schema::create('pila_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('pila_employers')->cascadeOnDelete();
            $table->string('operator', 30); // arus, simple, ...
            $table->string('username', 100);
            $table->text('password_encrypted');
            $table->boolean('is_active')->default(true);
            $table->timestamp('password_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['employer_id', 'operator']);
        });

        Schema::create('portal_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->nullable()->constrained('pila_employers')->nullOnDelete();
            $table->foreignId('affiliate_id')->nullable()->constrained('affiliates')->cascadeOnDelete();
            $table->string('entity_type', 10); // ARL, EPS, AFP, CCF
            $table->foreignId('entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();
            $table->string('username', 100)->nullable();
            $table->text('password_encrypted')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_not_applicable')->default(false);
            $table->timestamp('password_updated_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'entity_type']);
            $table->index(['employer_id', 'entity_type']);
        });

        Schema::create('affiliate_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->string('type', 20); // affiliation, payment, general
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['affiliate_id', 'type']);
        });

        Schema::create('credential_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('credential_kind', 20); // pila, portal
            $table->unsignedBigInteger('credential_id'); // id de pila_credentials o portal_credentials
            $table->string('action', 20); // viewed, updated, created
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['credential_kind', 'credential_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_audit_logs');
        Schema::dropIfExists('affiliate_notes');
        Schema::dropIfExists('portal_credentials');
        Schema::dropIfExists('pila_credentials');
        Schema::dropIfExists('pila_affiliations');
        Schema::dropIfExists('pila_risk_classes');
        Schema::dropIfExists('pila_social_entities');
        Schema::dropIfExists('pila_cotizante_types');
        Schema::dropIfExists('pila_employers');
    }
};

