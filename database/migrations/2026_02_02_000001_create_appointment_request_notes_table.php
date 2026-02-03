<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexName = 'ar_notes_req_created_at_idx';

        if (! Schema::hasTable('appointment_request_notes')) {
            Schema::create('appointment_request_notes', function (Blueprint $table) use ($indexName) {
                $table->id();
                $table->foreignId('appointment_request_id')
                    ->constrained('appointment_requests')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->text('note');
                $table->timestamps();

                // Nombre corto para evitar límite de MySQL (64 chars)
                $table->index(['appointment_request_id', 'created_at'], $indexName);
            });
        } else {
            // Si quedó creada por un intento fallido, aseguramos el índice con nombre corto.
            $hasIndex = ! empty(DB::select("SHOW INDEX FROM `appointment_request_notes` WHERE Key_name = ?", [$indexName]));
            if (! $hasIndex) {
                Schema::table('appointment_request_notes', function (Blueprint $table) use ($indexName) {
                    $table->index(['appointment_request_id', 'created_at'], $indexName);
                });
            }
        }

        // Backfill: convertir operator_notes existente a una primera nota (si aplica)
        if (Schema::hasColumn('appointment_requests', 'operator_notes')) {
            $notesCount = Schema::hasTable('appointment_request_notes')
                ? (int) DB::table('appointment_request_notes')->count()
                : 0;

            if ($notesCount > 0) {
                return;
            }

            $rows = DB::table('appointment_requests')
                ->select(['id', 'operator_notes', 'assigned_to', 'updated_at', 'created_at'])
                ->whereNotNull('operator_notes')
                ->where('operator_notes', '!=', '')
                ->get();

            foreach ($rows as $r) {
                DB::table('appointment_request_notes')->insert([
                    'appointment_request_id' => $r->id,
                    'user_id' => $r->assigned_to,
                    'note' => (string) $r->operator_notes,
                    'created_at' => $r->updated_at ?? $r->created_at ?? now(),
                    'updated_at' => $r->updated_at ?? $r->created_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_request_notes');
    }
};

