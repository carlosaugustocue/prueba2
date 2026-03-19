<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Limpieza de columnas legacy en pila_affiliations
        if (Schema::hasColumn('pila_affiliations', 'arl_entity_id') || Schema::hasColumn('pila_affiliations', 'ccf_entity_id') || Schema::hasColumn('pila_affiliations', 'eps_entity_id') || Schema::hasColumn('pila_affiliations', 'afp_entity_id')) {
            Schema::table('pila_affiliations', function (Blueprint $table) {
                if (Schema::hasColumn('pila_affiliations', 'arl_entity_id')) {
                    $table->dropConstrainedForeignId('arl_entity_id');
                }
                if (Schema::hasColumn('pila_affiliations', 'ccf_entity_id')) {
                    $table->dropConstrainedForeignId('ccf_entity_id');
                }
                if (Schema::hasColumn('pila_affiliations', 'eps_entity_id')) {
                    $table->dropConstrainedForeignId('eps_entity_id');
                }
                if (Schema::hasColumn('pila_affiliations', 'afp_entity_id')) {
                    $table->dropConstrainedForeignId('afp_entity_id');
                }
            });
        }

        // 2) Limpieza de columnas legacy en portal_credentials
        if (Schema::hasColumn('portal_credentials', 'entity_id')) {
            Schema::table('portal_credentials', function (Blueprint $table) {
                $table->dropConstrainedForeignId('entity_id');
            });
        }

        // 3) Eliminar tabla duplicada de entidades (se reusan eps/afps/arps/ccfs)
        Schema::dropIfExists('pila_social_entities');
    }

    public function down(): void
    {
        // Down simplificado: recrea tabla y columnas mínimas para rollback.
        Schema::create('pila_social_entities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);
            $table->string('code', 20);
            $table->string('name', 200);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['type', 'code']);
            $table->index(['type', 'name']);
        });

        Schema::table('pila_affiliations', function (Blueprint $table) {
            if (! Schema::hasColumn('pila_affiliations', 'arl_entity_id')) {
                $table->foreignId('arl_entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();
            }
            if (! Schema::hasColumn('pila_affiliations', 'ccf_entity_id')) {
                $table->foreignId('ccf_entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();
            }
            if (! Schema::hasColumn('pila_affiliations', 'eps_entity_id')) {
                $table->foreignId('eps_entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();
            }
            if (! Schema::hasColumn('pila_affiliations', 'afp_entity_id')) {
                $table->foreignId('afp_entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();
            }
        });

        Schema::table('portal_credentials', function (Blueprint $table) {
            if (! Schema::hasColumn('portal_credentials', 'entity_id')) {
                $table->foreignId('entity_id')->nullable()->constrained('pila_social_entities')->nullOnDelete();
            }
        });
    }
};

