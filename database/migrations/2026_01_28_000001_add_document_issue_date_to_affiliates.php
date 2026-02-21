<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha de expedición del documento (cédula, etc.) para cotizante y beneficiario.
     */
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->date('document_issue_date')->nullable()->after('document_number');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn('document_issue_date');
        });
    }
};
