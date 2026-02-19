<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 1 - Paso 1.1: tablas complementarias del módulo Seguridad Social.
     * Referencia: docs/PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md
     */
    public function up(): void
    {
        Schema::create('novelties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('novelty_type_id')->nullable()->constrained('novelty_types')->nullOnDelete();
            $table->date('effective_date');
            $table->string('description', 255)->nullable();
            $table->string('old_value', 255)->nullable();
            $table->string('new_value', 255)->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['affiliate_id', 'novelty_type_id']);
            $table->index('effective_date');
        });

        Schema::create('operator_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->string('provider_type', 50); // PAYMENT_OPERATOR, ARL, CCF, EPS, AFP
            $table->text('encrypted_credentials'); // JSON cifrado: usuario/clave
            $table->timestamps();
            $table->unique(['affiliate_id', 'provider_type']);
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->unsignedTinyInteger('month'); // 1-12
            $table->unsignedSmallInteger('year');
            $table->date('due_date');
            $table->string('status', 30)->default('PENDING'); // PENDING, SETTLED, SENT_TO_CLIENT, PAID, OVERDUE
            $table->decimal('health_amount', 12, 2)->nullable();
            $table->decimal('pension_amount', 12, 2)->nullable();
            $table->decimal('arl_amount', 12, 2)->nullable();
            $table->decimal('ccf_amount', 12, 2)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['affiliate_id', 'year', 'month']);
            $table->index(['year', 'month']);
            $table->index('due_date');
            $table->index('status');
        });

        Schema::create('support_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->string('title', 255);
            $table->string('file_path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
            $table->index(['affiliate_id', 'payroll_id']);
        });

        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->nullable()->constrained('affiliates')->nullOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->string('channel', 30); // whatsapp, email
            $table->string('type', 50)->nullable(); // reminder, confirmation, overdue
            $table->string('status', 30)->default('sent'); // sent, failed
            $table->string('recipient', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['affiliate_id', 'channel']);
            $table->index('sent_at');
        });

        Schema::create('payroll_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->string('event', 100);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['payroll_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('social_security_role', 30)->nullable()->after('role_id'); // affiliations, payments, reports, admin
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('social_security_role');
        });
        Schema::dropIfExists('payroll_trackings');
        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('support_documents');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('operator_credentials');
        Schema::dropIfExists('novelties');
    }
};
