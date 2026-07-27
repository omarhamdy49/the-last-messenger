<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The admin data table's search box is scoped to name/kunya/laqab only
 * (not full_name, which holds multi-generation nasab chains — searching
 * "Amr" against the existing 4-column FULLTEXT index matched any companion
 * with an ancestor named Amr buried in their lineage, not companions named
 * Amr themselves). MySQL's MATCH() must reference an index whose column
 * list matches exactly, so this adds a dedicated 3-column index instead of
 * reusing `fulltext_name_fields`.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE sahabi_translations ADD FULLTEXT fulltext_name_kunya_laqab (name, kunya, laqab)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE sahabi_translations DROP INDEX fulltext_name_kunya_laqab');
        }
    }
};
