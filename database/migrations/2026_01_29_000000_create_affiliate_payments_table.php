<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pagos registrados por afiliado (recibos de caja, facturas, órdenes de pago, etc.).
     */
    public function up(): void
    {
        Schema::create('affiliate_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->foreignId('accounting_registry_id')->nullable()->constrained('accounting_registries')->nullOnDelete();

            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('external_number', 100)->nullable();
            $table->string('description', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['affiliate_id', 'payment_date']);
            $table->index(['payroll_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payments');
    }
};

