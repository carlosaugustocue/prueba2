<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examenes_fisicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuentro_id')->unique()->constrained('encuentros_clinicos')->cascadeOnDelete();
            $table->decimal('peso_kg', 6, 2)->nullable();
            $table->decimal('talla_cm', 5, 2)->nullable();
            $table->decimal('imc', 5, 2)->nullable();
            $table->unsignedSmallInteger('presion_arterial_sistolica')->nullable();
            $table->unsignedSmallInteger('presion_arterial_diastolica')->nullable();
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable();
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable();
            $table->decimal('temperatura', 4, 2)->nullable();
            $table->unsignedTinyInteger('saturacion_oxigeno')->nullable();
            $table->json('hallazgos_por_sistema')->nullable();
            $table->text('resumen_general')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes_fisicos');
    }
};
