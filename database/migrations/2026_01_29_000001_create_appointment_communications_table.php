<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Por ahora solo "phone", pero se deja abierto a futuro (whatsapp/email manual)
            $table->string('channel', 20)->default('phone')->index();

            // Categoría de llamada (solo aplica a canal phone)
            $table->string('category', 30)->nullable()->index();

            // Nota libre
            $table->string('note', 500)->nullable();

            $table->timestamps();

            $table->index(['appointment_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_communications');
    }
};

