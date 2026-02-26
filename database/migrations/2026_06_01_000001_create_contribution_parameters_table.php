<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parámetros de aportes y valores de sistema con vigencia temporal.
     * Evita magic numbers y hardcoding: todos los porcentajes y topes se leen desde aquí.
     * Referencia: docs/ARQUITECTURA_Y_PLAN_FEATURE_SEGURIDAD_SOCIAL.md
     */
    public function up(): void
    {
        Schema::create('contribution_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->comment('HEALTH, PENSION, ARL, CCF, SENA, ICBF, FSP, SYSTEM');
            $table->string('subtype', 50)->comment('TOTAL, EMPLOYER, EMPLOYEE, INDEPENDENT, RISK_1..5, SMLMV, etc.');
            $table->decimal('value', 14, 4)->comment('Porcentaje como decimal, monto en pesos, o multiplicador');
            $table->string('value_type', 20)->default('PERCENTAGE')->comment('PERCENTAGE | AMOUNT | MULTIPLIER');
            $table->date('valid_from')->comment('Inicio de vigencia');
            $table->date('valid_to')->nullable()->comment('Fin de vigencia; NULL = vigente indefinidamente');
            $table->string('description', 255)->nullable();
            $table->string('legal_reference', 255)->nullable()->comment('Ej: Decreto 1469/2025');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'subtype']);
            $table->index('valid_from');
            $table->index('valid_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_parameters');
    }
};
