<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FULLTEXT + columnas generadas son específicas de MySQL.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Verificación: si no existe la columna `full_name`, la creamos como generada
        // para poder indexarla con FULLTEXT (matching rápido para búsquedas por nombre).
        if (! Schema::hasColumn('affiliates', 'full_name')) {
            DB::statement("
                ALTER TABLE affiliates
                ADD COLUMN full_name VARCHAR(255)
                GENERATED ALWAYS AS (
                    CONCAT_WS(' ', first_name, second_name, last_name, second_last_name)
                ) STORED
            ");
        }

        $indexName = 'affiliates_full_name_document_number_fulltext';

        $indexExists = DB::selectOne("
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'affiliates'
              AND INDEX_NAME = ?
              AND INDEX_TYPE = 'FULLTEXT'
            LIMIT 1
        ", [$indexName]);

        if ($indexExists) {
            return;
        }

        Schema::table('affiliates', function (Blueprint $table) use ($indexName) {
            $table->fullText(['full_name', 'document_number'], $indexName);
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $indexName = 'affiliates_full_name_document_number_fulltext';

        // Borrado seguro del FULLTEXT.
        DB::statement("
            ALTER TABLE affiliates
            DROP INDEX {$indexName}
        ");

        // Solo intentamos borrar la columna generada si realmente es una columna generada.
        $col = DB::selectOne("
            SELECT GENERATION_EXPRESSION
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'affiliates'
              AND COLUMN_NAME = 'full_name'
        ");

        if ($col && ! empty($col->GENERATION_EXPRESSION)) {
            DB::statement('ALTER TABLE affiliates DROP COLUMN full_name');
        }
    }
};

