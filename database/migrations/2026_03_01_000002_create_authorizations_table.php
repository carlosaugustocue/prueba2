<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('appointment_request_id')->nullable()->constrained('appointment_requests')->nullOnDelete();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('eps_id')->nullable()->constrained('eps')->nullOnDelete();
            $table->string('service_type', 100);
            $table->text('diagnosis_or_reason')->nullable();
            $table->string('status', 30);
            $table->date('radicated_at')->nullable();
            $table->string('authorization_number', 100)->nullable();
            $table->string('authorized_ips_name', 255)->nullable();
            $table->date('valid_until')->nullable();
            $table->text('denial_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['affiliate_id', 'status']);
            $table->index('radicated_at');
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorizations');
    }
};
