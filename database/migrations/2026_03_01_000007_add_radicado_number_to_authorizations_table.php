<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authorizations', function (Blueprint $table) {
            $table->string('radicado_number', 100)->nullable()->after('radicated_at')->comment('Número de radicado que entrega la EPS para seguimiento');
        });
    }

    public function down(): void
    {
        Schema::table('authorizations', function (Blueprint $table) {
            $table->dropColumn('radicado_number');
        });
    }
};
