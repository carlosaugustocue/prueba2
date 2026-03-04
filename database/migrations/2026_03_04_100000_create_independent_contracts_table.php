<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('independent_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('payer_id')->nullable()->constrained('payers')->nullOnDelete();
            $table->string('contract_reference', 100)->nullable();
            $table->string('contract_type', 40)->default('SERVICE_PROVISION');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('monthly_income', 14, 2);
            $table->unsignedTinyInteger('risk_class')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('independent_contracts');
    }
};

