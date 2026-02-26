<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historia_clinica', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('numero_historia', 50)->unique();
            $table->foreignId('affiliate_id')->unique()->constrained('affiliates')->cascadeOnDelete();
            $table->date('fecha_apertura');
            $table->string('estado', 20)->default('ACTIVA'); // ACTIVA | INACTIVA | ARCHIVADA
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('firma_digital', 64)->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historia_clinica');
    }
};
