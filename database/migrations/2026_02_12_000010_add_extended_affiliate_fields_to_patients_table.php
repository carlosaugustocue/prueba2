<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Datos de nombre/apellidos extendidos
            $table->string('second_name', 100)->nullable()->after('first_name');
            $table->string('second_last_name', 100)->nullable()->after('last_name');

            // Datos demográficos y de contacto adicionales
            $table->string('gender', 10)->nullable()->after('birth_date'); // Ej: M, F, Otro
            $table->string('neighborhood', 100)->nullable()->after('address');
            $table->string('phone_2', 20)->nullable()->after('phone');

            // Información de seguridad social
            $table->string('afp_name', 150)->nullable()->after('eps_id');      // Fondo de pensiones
            $table->string('arp_name', 150)->nullable()->after('afp_name');    // Aseguradora de Riesgos Laborales
            $table->string('arp_risk_class', 20)->nullable()->after('arp_name');

            // Estado / descripción corta del afiliado (ej: ACTIVO)
            $table->string('status', 30)->nullable()->default('ACTIVO')->after('notes');

            $table->index('gender');
            $table->index('neighborhood');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['gender']);
            $table->dropIndex(['neighborhood']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'second_name',
                'second_last_name',
                'gender',
                'neighborhood',
                'phone_2',
                'afp_name',
                'arp_name',
                'arp_risk_class',
                'status',
            ]);
        });
    }
};

