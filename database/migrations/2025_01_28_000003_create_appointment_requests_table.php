<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Paciente que solicita
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            
            // Tipo de cita solicitada
            $table->string('type', 30);
            $table->string('priority', 20)->default('medium');
            $table->string('specialty', 100)->nullable();
            
            // Estado de la solicitud
            $table->string('status', 30)->default('pending');
            
            // Fechas de seguimiento
            $table->timestamp('requested_at');           // Cuando el cliente llamó/solicitó
            $table->timestamp('started_at')->nullable(); // Cuando la operadora comenzó a tramitar
            $table->timestamp('completed_at')->nullable(); // Cuando se obtuvo la cita o se cerró
            
            // Notas
            $table->text('client_notes')->nullable();    // Lo que el cliente indica
            $table->text('operator_notes')->nullable();  // Notas de la operadora
            
            // Relación con la cita obtenida (si se completó exitosamente)
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            
            // Usuarios
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para consultas frecuentes
            $table->index('status');
            $table->index('requested_at');
            $table->index(['status', 'requested_at']);
            $table->index('assigned_to');
        });

        // Agregar referencia en appointments para saber de qué solicitud viene
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('appointment_request_id')->nullable()->after('uuid')->constrained('appointment_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['appointment_request_id']);
            $table->dropColumn('appointment_request_id');
        });
        
        Schema::dropIfExists('appointment_requests');
    }
};
