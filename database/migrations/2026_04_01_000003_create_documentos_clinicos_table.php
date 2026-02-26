<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_clinicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historia_clinica_id')->constrained('historia_clinica')->cascadeOnDelete();
            $table->string('tipo', 30); // LABORATORIO | IMAGEN | CONSENTIMIENTO | EXTERNO
            $table->string('nombre_archivo');
            $table->string('ruta_almacenamiento');
            $table->string('hash_integridad', 64)->nullable();
            $table->date('fecha_documento')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('historia_clinica_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_clinicos');
    }
};
