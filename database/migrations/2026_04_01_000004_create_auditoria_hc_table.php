<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_hc', function (Blueprint $table) {
            $table->id();
            $table->string('tabla_afectada', 64);
            $table->string('registro_id', 64);
            $table->string('accion', 16); // CREATE | READ | UPDATE
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_origen', 45)->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tabla_afectada', 'registro_id']);
            $table->index(['usuario_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_hc');
    }
};
