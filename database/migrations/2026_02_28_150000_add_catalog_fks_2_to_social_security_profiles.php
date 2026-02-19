<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añadir FKs a client_types, contributor_types, accounting_registries en social_security_profiles.
     * Migrar datos desde columnas texto y eliminar estas.
     * Inserta datos mínimos en catálogos si no existen (para poder migrar).
     */
    public function up(): void
    {
        $this->seedMinimalCatalogs();

        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->foreignId('client_type_id')->nullable()->after('affiliate_id')->constrained('client_types')->nullOnDelete();
            $table->foreignId('contributor_type_id')->nullable()->after('client_type_id')->constrained('contributor_types')->nullOnDelete();
            $table->foreignId('accounting_registry_id')->nullable()->after('payment_operator_id')->constrained('accounting_registries')->nullOnDelete();
        });

        $this->migrateClientTypeIds();
        $this->migrateContributorTypeIds();
        $this->migrateAccountingRegistryIds();

        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->dropColumn(['client_type', 'contributor_type', 'accounting_registry']);
        });
    }

    private function seedMinimalCatalogs(): void
    {
        if (DB::table('client_types')->doesntExist()) {
            DB::table('client_types')->insert([
                ['name' => 'SERVICONLI', 'code' => 'SERVICONLI', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'INDEPENDENT', 'code' => 'INDEPENDENT', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'DEPENDENT', 'code' => 'DEPENDENT', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'FOREIGN_RESIDENT', 'code' => 'FOREIGN_RESIDENT', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $contributorRows = [
            ['01', 'Dependiente'],
            ['02', 'Servicio Doméstico'],
            ['03', 'Independiente'],
            ['40', 'Beneficiario UPC adicional'],
            ['51', 'Tiempo parcial subsidiado'],
            ['57', 'Independiente voluntario ARL'],
            ['59', 'Prestación de servicios'],
        ];
        foreach ($contributorRows as [$code, $name]) {
            DB::table('contributor_types')->insertOrIgnore([
                'code' => $code,
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('accounting_registries')->count() === 0) {
            DB::table('accounting_registries')->insert([
                ['name' => 'RECIBO_CAJA', 'code' => 'RECIBO_CAJA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'FACTURA_ELECTRONICA', 'code' => 'FACTURA_ELECTRONICA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    private function migrateClientTypeIds(): void
    {
        $profiles = DB::table('social_security_profiles')->whereNotNull('client_type')->get(['id', 'client_type']);
        $types = DB::table('client_types')->get(['id', 'name', 'code']);

        foreach ($profiles as $profile) {
            $val = trim($profile->client_type);
            $match = $types->first(fn ($t) => strcasecmp(trim($t->name), $val) === 0 || strcasecmp(trim($t->code ?? ''), $val) === 0);
            if ($match) {
                DB::table('social_security_profiles')->where('id', $profile->id)->update(['client_type_id' => $match->id]);
            }
        }
    }

    private function migrateContributorTypeIds(): void
    {
        $profiles = DB::table('social_security_profiles')->whereNotNull('contributor_type')->where('contributor_type', '!=', '')->get(['id', 'contributor_type']);
        $types = DB::table('contributor_types')->get(['id', 'code']);

        foreach ($profiles as $profile) {
            $code = trim($profile->contributor_type);
            $match = $types->first(fn ($t) => trim($t->code) === $code);
            if ($match) {
                DB::table('social_security_profiles')->where('id', $profile->id)->update(['contributor_type_id' => $match->id]);
            }
        }
    }

    private function migrateAccountingRegistryIds(): void
    {
        $profiles = DB::table('social_security_profiles')->whereNotNull('accounting_registry')->where('accounting_registry', '!=', '')->get(['id', 'accounting_registry']);
        $registries = DB::table('accounting_registries')->get(['id', 'name', 'code']);

        foreach ($profiles as $profile) {
            $val = trim($profile->accounting_registry);
            $match = $registries->first(fn ($r) => strcasecmp(trim($r->name), $val) === 0 || strcasecmp(trim($r->code ?? ''), $val) === 0);
            if ($match) {
                DB::table('social_security_profiles')->where('id', $profile->id)->update(['accounting_registry_id' => $match->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->dropForeign(['client_type_id']);
            $table->dropForeign(['contributor_type_id']);
            $table->dropForeign(['accounting_registry_id']);
        });

        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->string('client_type', 30)->nullable()->after('affiliate_id');
            $table->string('contributor_type', 10)->nullable()->after('client_type_id');
            $table->string('accounting_registry', 50)->nullable()->after('payment_operator_id');
        });

        // Optionally repopulate from catalog (simplified: just set first value)
        $ct = DB::table('client_types')->first();
        if ($ct) {
            DB::table('social_security_profiles')->whereNotNull('client_type_id')->update(['client_type' => $ct->name]);
        }
        $contrib = DB::table('contributor_types')->first();
        if ($contrib) {
            DB::table('social_security_profiles')->whereNotNull('contributor_type_id')->update(['contributor_type' => $contrib->code]);
        }
        $ar = DB::table('accounting_registries')->first();
        if ($ar) {
            DB::table('social_security_profiles')->whereNotNull('accounting_registry_id')->update(['accounting_registry' => $ar->name]);
        }

        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->dropColumn(['client_type_id', 'contributor_type_id', 'accounting_registry_id']);
        });
    }
};
