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
        $tableName = Schema::hasTable('affiliates') ? 'affiliates' : (Schema::hasTable('patients') ? 'patients' : null);
        if (! $tableName) {
            return;
        }

        if (Schema::hasColumn($tableName, 'document_issue_date')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->date('document_issue_date')->nullable()->after('document_number');
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('affiliates') ? 'affiliates' : (Schema::hasTable('patients') ? 'patients' : null);
        if (! $tableName) {
            return;
        }

        if (Schema::hasColumn($tableName, 'document_issue_date')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('document_issue_date');
            });
        }
    }
};
