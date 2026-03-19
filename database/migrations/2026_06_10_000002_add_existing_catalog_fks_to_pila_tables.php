<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pila_affiliations', function (Blueprint $table) {
            // Reusar catálogos existentes (evitar duplicación): eps, afps, arps, ccfs
            $table->foreignId('eps_id')->nullable()->after('eps_entity_id')->constrained('eps')->nullOnDelete();
            $table->foreignId('afp_id')->nullable()->after('afp_entity_id')->constrained('afps')->nullOnDelete();
            $table->foreignId('arp_id')->nullable()->after('arl_entity_id')->constrained('arps')->nullOnDelete();
            $table->foreignId('ccf_id')->nullable()->after('ccf_entity_id')->constrained('ccfs')->nullOnDelete();
        });

        Schema::table('portal_credentials', function (Blueprint $table) {
            $table->foreignId('eps_id')->nullable()->after('entity_id')->constrained('eps')->nullOnDelete();
            $table->foreignId('afp_id')->nullable()->after('eps_id')->constrained('afps')->nullOnDelete();
            $table->foreignId('arp_id')->nullable()->after('afp_id')->constrained('arps')->nullOnDelete();
            $table->foreignId('ccf_id')->nullable()->after('arp_id')->constrained('ccfs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('portal_credentials', function (Blueprint $table) {
            $table->dropForeign(['eps_id']);
            $table->dropForeign(['afp_id']);
            $table->dropForeign(['arp_id']);
            $table->dropForeign(['ccf_id']);
            $table->dropColumn(['eps_id', 'afp_id', 'arp_id', 'ccf_id']);
        });

        Schema::table('pila_affiliations', function (Blueprint $table) {
            $table->dropForeign(['eps_id']);
            $table->dropForeign(['afp_id']);
            $table->dropForeign(['arp_id']);
            $table->dropForeign(['ccf_id']);
            $table->dropColumn(['eps_id', 'afp_id', 'arp_id', 'ccf_id']);
        });
    }
};

