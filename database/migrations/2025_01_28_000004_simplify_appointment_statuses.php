<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Simplifica los estados de citas:
     * - pending, in_progress, confirmed, sent, completed → confirmed
     * - cancelled → cancelled
     */
    public function up(): void
    {
        // Actualizar todos los estados activos a 'confirmed'
        DB::table('appointments')
            ->whereIn('status', ['pending', 'in_progress', 'sent', 'completed'])
            ->update(['status' => 'confirmed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es posible revertir esta migración de manera precisa
        // ya que no sabemos cuál era el estado original de cada cita
    }
};
