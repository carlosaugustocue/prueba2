<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refactor: patients → affiliates, datos SS → social_security_profiles.
     * Orden según docs/PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md (Fase 0).
     */
    public function up(): void
    {
        // 1. Crear tabla payers (quién paga)
        Schema::create('payers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_type', 10);
            $table->string('document_number', 20)->unique();
            $table->string('address', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('contact_person', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Renombrar patients → affiliates (FK holder_id se rompe al renombrar; la recreamos después)
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['holder_id']);
        });
        Schema::rename('patients', 'affiliates');
        Schema::table('affiliates', function (Blueprint $table) {
            $table->foreign('holder_id')->references('id')->on('affiliates')->nullOnDelete();
        });

        // 3. Campos nuevos en affiliates (mapeo DataSegura: city, department)
        Schema::table('affiliates', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('neighborhood');
            $table->string('department', 100)->nullable()->after('city');
        });

        // 4. Crear tabla social_security_profiles
        Schema::create('social_security_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->unique()->constrained('affiliates')->cascadeOnDelete();
            $table->string('client_type', 30); // INDEPENDENT, DEPENDENT, SERVICONLI, FOREIGN_RESIDENT
            $table->string('contributor_type', 10)->nullable();
            $table->decimal('ibc', 12, 2)->nullable();
            $table->foreignId('eps_id')->nullable()->constrained('eps')->nullOnDelete();
            $table->string('afp_name', 150)->nullable();
            $table->string('arp_name', 150)->nullable();
            $table->string('arp_risk_class', 20)->nullable();
            $table->string('ccf_name', 100)->nullable();
            $table->foreignId('payer_id')->nullable()->constrained('payers')->nullOnDelete();
            $table->string('payment_operator', 100)->nullable();
            $table->tinyInteger('payment_day')->nullable();
            $table->string('payment_periodicity', 20)->nullable(); // CURRENT, OVERDUE
            $table->boolean('has_parafiscales')->default(false);
            $table->string('accounting_registry', 50)->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        // 5. Migrar datos SS de affiliates a social_security_profiles (solo quienes tengan al menos un dato)
        // CURRENT_TIMESTAMP es portable (MySQL/SQLite). Evitar NOW() para compatibilidad en tests.
        DB::statement("
            INSERT INTO social_security_profiles
                (affiliate_id, client_type, contributor_type, eps_id, afp_name, arp_name, arp_risk_class, created_at, updated_at)
            SELECT
                id,
                'SERVICONLI',
                '01',
                eps_id,
                afp_name,
                arp_name,
                arp_risk_class,
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            FROM affiliates
            WHERE eps_id IS NOT NULL OR afp_name IS NOT NULL OR arp_name IS NOT NULL
        ");

        // 6. Eliminar columnas de SS de affiliates (la FK conserva el nombre original de la tabla "patients")
        Schema::table('affiliates', function (Blueprint $table) {
            // SQLite no soporta dropForeign por nombre; usar columnas.
            if (DB::getDriverName() === 'sqlite') {
                $table->dropForeign(['eps_id']);
            } else {
                $table->dropForeign('patients_eps_id_foreign');
            }
        });
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn(['eps_id', 'afp_name', 'arp_name', 'arp_risk_class']);
        });

        // 7. appointments: patient_id → affiliate_id
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('patient_id', 'affiliate_id');
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('affiliate_id')->references('id')->on('affiliates')->cascadeOnDelete();
        });

        // 8. appointment_requests: patient_id → affiliate_id
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->renameColumn('patient_id', 'affiliate_id');
        });
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->foreign('affiliate_id')->references('id')->on('affiliates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Revertir appointment_requests
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
        });
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->renameColumn('affiliate_id', 'patient_id');
        });

        // Revertir appointments
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('affiliate_id', 'patient_id');
        });

        // Restaurar columnas SS en affiliates (desde profiles; solo primeros valores por affiliate)
        Schema::table('affiliates', function (Blueprint $table) {
            $table->foreignId('eps_id')->nullable()->after('notes')->constrained('eps')->nullOnDelete();
            $table->string('afp_name', 150)->nullable()->after('eps_id');
            $table->string('arp_name', 150)->nullable()->after('afp_name');
            $table->string('arp_risk_class', 20)->nullable()->after('arp_name');
        });
        DB::statement("
            UPDATE affiliates a
            INNER JOIN social_security_profiles p ON p.affiliate_id = a.id
            SET a.eps_id = p.eps_id, a.afp_name = p.afp_name, a.arp_name = p.arp_name, a.arp_risk_class = p.arp_risk_class
        ");

        Schema::dropIfExists('social_security_profiles');

        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn(['city', 'department']);
        });

        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropForeign(['holder_id']);
        });
        Schema::rename('affiliates', 'patients');
        Schema::table('patients', function (Blueprint $table) {
            $table->foreign('holder_id')->references('id')->on('patients')->nullOnDelete();
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });

        Schema::dropIfExists('payers');
    }
};
