<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Días trabajados en el mes (tipo cotizante 51 - independiente flexible).
     * Circular 093/2025: aportes proporcionales por período. Si null, se asume mes completo (30 días).
     */
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedTinyInteger('days_worked')->nullable()->after('total_amount')
                ->comment('Días trabajados en el mes para tipo 51 (1-30); null = mes completo');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('days_worked');
        });
    }
};
