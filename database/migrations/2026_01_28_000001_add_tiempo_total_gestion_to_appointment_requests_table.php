<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_requests', function (Blueprint $table) {
            // Tiempo total entre solicitud (requested_at) y cierre (completed_at), en minutos.
            $table->unsignedInteger('tiempo_total_gestion')->nullable()->after('completed_at')->index();
        });

        // Backfill para registros ya cerrados
        DB::statement("
            UPDATE appointment_requests
            SET tiempo_total_gestion = TIMESTAMPDIFF(MINUTE, requested_at, completed_at)
            WHERE requested_at IS NOT NULL
              AND completed_at IS NOT NULL
              AND tiempo_total_gestion IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->dropColumn('tiempo_total_gestion');
        });
    }
};

