<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_state_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authorization_id')->constrained('authorizations')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at');

            $table->index('authorization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_state_histories');
    }
};
