<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Festivos de Colombia para cálculo de día hábil (PILA).
     * Referencia: Decreto 1990 de 2016, Decreto 780 de 2016.
     * El vencimiento de pago es el N-ésimo día hábil del mes siguiente (N = 2..16);
     * no se consideran hábiles sábado, domingo ni festivos.
     */
    public function up(): void
    {
        Schema::create('colombian_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name', 150)->nullable();
            $table->timestamps();
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colombian_holidays');
    }
};
