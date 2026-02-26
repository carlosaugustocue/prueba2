<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antecedentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historia_clinica_id')->constrained('historia_clinica')->cascadeOnDelete();
            $table->string('tipo', 30); // PATOLOGICO | QUIRURGICO | FARMACOLOGICO | ALERGICO | FAMILIAR | TOXICO | GINECO_OBSTETRICO
            $table->text('descripcion');
            $table->date('fecha_registro');
            $table->foreignId('profesional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['historia_clinica_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antecedentes');
    }
};
