<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authorization_id')->constrained('authorizations')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('authorization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_documents');
    }
};
