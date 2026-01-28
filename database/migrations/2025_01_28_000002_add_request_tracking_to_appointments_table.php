<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Fecha en que el cliente solicitó la cita a Serviconli
            $table->timestamp('requested_at')->nullable()->after('uuid');
            
            // Fecha en que se tramitó/gestionó la cita (cuando se pasa a confirmed o in_progress)
            $table->timestamp('processed_at')->nullable()->after('requested_at');
            
            // Índice para consultas de eficiencia
            $table->index('requested_at');
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['requested_at']);
            $table->dropIndex(['processed_at']);
            $table->dropColumn(['requested_at', 'processed_at']);
        });
    }
};
