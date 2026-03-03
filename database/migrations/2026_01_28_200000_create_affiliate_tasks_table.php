<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tareas internas asociadas a un afiliado (por área: cartera, seguridad_social, etc.).
     */
    public function up(): void
    {
        Schema::create('affiliate_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->string('area', 50); // cartera, seguridad_social, etc.
            $table->string('description', 255);
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'area', 'is_completed']);
            $table->index(['area', 'is_completed']);
            $table->index('is_completed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_tasks');
    }
};

