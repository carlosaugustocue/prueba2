<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añadir FKs a catálogos en social_security_profiles, migrar datos desde columnas texto y eliminar estas.
     * Fase 1 - Paso 1.0d. Ejecutar después de create_ss_catalog_tables y de haber ejecutado los seeders.
     */
    public function up(): void
    {
        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->foreignId('afp_id')->nullable()->after('eps_id')->constrained('afps')->nullOnDelete();
            $table->foreignId('arp_id')->nullable()->after('arp_risk_class')->constrained('arps')->nullOnDelete();
            $table->foreignId('ccf_id')->nullable()->after('payer_id')->constrained('ccfs')->nullOnDelete();
            $table->foreignId('payment_operator_id')->nullable()->after('ccf_id')->constrained('payment_operators')->nullOnDelete();
        });

        // Migrar datos: asignar *_id según coincidencia por nombre (insensible a mayúsculas y espacios)
        $this->migrateAfpIds();
        $this->migrateArpIds();
        $this->migrateCcfIds();
        $this->migratePaymentOperatorIds();

        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->dropColumn(['afp_name', 'arp_name', 'ccf_name', 'payment_operator']);
        });
    }

    private function migrateAfpIds(): void
    {
        $profiles = DB::table('social_security_profiles')->whereNotNull('afp_name')->where('afp_name', '!=', '')->get(['id', 'afp_name']);
        $afps = DB::table('afps')->get(['id', 'name']);

        foreach ($profiles as $profile) {
            $name = trim($profile->afp_name);
            $match = $afps->first(fn ($a) => strcasecmp(trim($a->name), $name) === 0);
            if ($match) {
                DB::table('social_security_profiles')->where('id', $profile->id)->update(['afp_id' => $match->id]);
            }
        }
    }

    private function migrateArpIds(): void
    {
        $profiles = DB::table('social_security_profiles')->whereNotNull('arp_name')->where('arp_name', '!=', '')->get(['id', 'arp_name']);
        $arps = DB::table('arps')->get(['id', 'name']);

        foreach ($profiles as $profile) {
            $name = trim($profile->arp_name);
            $match = $arps->first(fn ($a) => strcasecmp(trim($a->name), $name) === 0);
            if ($match) {
                DB::table('social_security_profiles')->where('id', $profile->id)->update(['arp_id' => $match->id]);
            }
        }
    }

    private function migrateCcfIds(): void
    {
        $profiles = DB::table('social_security_profiles')->whereNotNull('ccf_name')->where('ccf_name', '!=', '')->get(['id', 'ccf_name']);
        $ccfs = DB::table('ccfs')->get(['id', 'name']);

        foreach ($profiles as $profile) {
            $name = trim($profile->ccf_name);
            $match = $ccfs->first(fn ($a) => strcasecmp(trim($a->name), $name) === 0);
            if ($match) {
                DB::table('social_security_profiles')->where('id', $profile->id)->update(['ccf_id' => $match->id]);
            }
        }
    }

    private function migratePaymentOperatorIds(): void
    {
        $profiles = DB::table('social_security_profiles')->whereNotNull('payment_operator')->where('payment_operator', '!=', '')->get(['id', 'payment_operator']);
        $operators = DB::table('payment_operators')->get(['id', 'name']);

        foreach ($profiles as $profile) {
            $name = trim($profile->payment_operator);
            $match = $operators->first(fn ($a) => strcasecmp(trim($a->name), $name) === 0);
            if ($match) {
                DB::table('social_security_profiles')->where('id', $profile->id)->update(['payment_operator_id' => $match->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->dropForeign(['afp_id']);
            $table->dropForeign(['arp_id']);
            $table->dropForeign(['ccf_id']);
            $table->dropForeign(['payment_operator_id']);
        });

        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->dropColumn(['afp_id', 'arp_id', 'ccf_id', 'payment_operator_id']);
        });

        Schema::table('social_security_profiles', function (Blueprint $table) {
            $table->string('afp_name', 150)->nullable()->after('eps_id');
            $table->string('arp_name', 150)->nullable()->after('arp_risk_class');
            $table->string('ccf_name', 100)->nullable()->after('payer_id');
            $table->string('payment_operator', 100)->nullable()->after('payer_id');
        });
    }
};
