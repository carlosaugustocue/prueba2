<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('document_type', 10);
            $table->string('document_number', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->foreignId('eps_id')->nullable()->constrained('eps')->nullOnDelete();
            $table->string('patient_type', 20);
            $table->foreignId('holder_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('document_number');
            $table->index(['first_name', 'last_name']);
            $table->index('patient_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
