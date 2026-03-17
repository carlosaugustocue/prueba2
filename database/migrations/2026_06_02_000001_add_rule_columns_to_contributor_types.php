<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contributor_types', function (Blueprint $table) {
            $table->boolean('is_dependent')->default(false)->after('description');
            $table->boolean('parafiscales_allowed')->default(false)->after('is_dependent');
            $table->boolean('health_applies')->default(true)->after('parafiscales_allowed');
            $table->boolean('pension_applies')->default(true)->after('health_applies');
            $table->boolean('arl_applies')->default(true)->after('pension_applies');
            $table->boolean('ccf_applies')->default(false)->after('arl_applies');
            $table->boolean('is_proportional')->default(false)->after('ccf_applies');
        });
    }

    public function down(): void
    {
        Schema::table('contributor_types', function (Blueprint $table) {
            $table->dropColumn([
                'is_dependent',
                'parafiscales_allowed',
                'health_applies',
                'pension_applies',
                'arl_applies',
                'ccf_applies',
                'is_proportional',
            ]);
        });
    }
};
