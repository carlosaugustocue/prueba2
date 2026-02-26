<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos para trazabilidad de cálculos y desglose adicional (parafiscales, FSP).
     * Referencia: docs/ARQUITECTURA_Y_PLAN_FEATURE_SEGURIDAD_SOCIAL.md
     */
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->json('calculation_metadata')->nullable()->after('notes')
                ->comment('Snapshot de parámetros y desglose usados en el cálculo para auditoría');
            $table->decimal('parafiscal_amount', 12, 2)->nullable()->after('ccf_amount')
                ->comment('SENA + ICBF cuando aplica');
            $table->decimal('fsp_amount', 12, 2)->nullable()->after('parafiscal_amount')
                ->comment('Fondo de Solidaridad Pensional');
            $table->decimal('total_amount', 12, 2)->nullable()->after('fsp_amount')
                ->comment('Total aportes (health + pension + arl + ccf + parafiscal + fsp)');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'calculation_metadata',
                'parafiscal_amount',
                'fsp_amount',
                'total_amount',
            ]);
        });
    }
};
