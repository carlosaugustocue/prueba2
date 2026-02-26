<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuentros_clinicos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('historia_clinica_id')->constrained('historia_clinica')->cascadeOnDelete();
            $table->string('tipo_atencion', 30); // CONSULTA | URGENCIA | HOSPITALIZACION | TELECONSULTA
            $table->date('fecha_atencion');
            $table->foreignId('profesional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('especialidad_id')->nullable();
            $table->text('motivo_consulta');
            $table->text('enfermedad_actual')->nullable();
            $table->text('estado_mental')->nullable();
            $table->string('firma_digital', 64)->nullable();
            $table->timestamps();

            $table->index(['historia_clinica_id', 'fecha_atencion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuentros_clinicos');
    }
};
