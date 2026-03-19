<?php

use App\Modules\Affiliates\Enums\AffiliateStatus;
use App\Modules\Affiliates\Models\Affiliate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('affiliates', 'appointment_eligible')) {
            return;
        }

        Schema::table('affiliates', function (Blueprint $table) {
            $table->boolean('appointment_eligible')->default(false)->after('status');
        });

        // Backfill:
        // "Elegible" debe coincidir con las validaciones actuales para crear solicitud/cita:
        // - estado ACTIVO
        // - gestionado por Serviconli (pagador serviconli o client type SERVICONLI)
        Affiliate::query()
            ->where('status', AffiliateStatus::ACTIVO->value)
            ->whereServiconliManaged()
            ->update(['appointment_eligible' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('affiliates', 'appointment_eligible')) {
            return;
        }

        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn('appointment_eligible');
        });
    }
};

