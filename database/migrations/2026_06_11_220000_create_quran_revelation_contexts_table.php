<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Short explicit names — the auto-generated FK name for this table exceeds
     * MySQL's 64-character identifier limit.
     */
    private const FK_CONTEXT = 'qrc_translations_context_fk';

    private const IDX_LOCALE = 'qrc_translations_locale_index';

    private const UNIQUE_CONTEXT_LOCALE = 'qrc_translations_context_locale_unique';

    private const IDX_FULLTEXT = 'fulltext_title_narrative';

    /**
     * Run the migrations.
     *
     * Creation is guarded with hasTable()/index checks: a previous run of this
     * migration failed midway on MySQL (FK identifier too long), leaving the
     * tables partially created without being recorded in `migrations`. The
     * guards let this migration converge to the full schema from either a
     * clean database or that partial state.
     */
    public function up(): void
    {
        if (! Schema::hasTable('quran_revelation_contexts')) {
            Schema::create('quran_revelation_contexts', function (Blueprint $table) {
                $table->id();
                // constrained() creates the index MySQL needs for the FK, which also
                // serves the surah filter on the public listing (no duplicate index).
                $table->foreignId('surah_id')->constrained('quran_surahs')->cascadeOnDelete();
                $table->unsignedInteger('verse_from');
                $table->unsignedInteger('verse_to')->nullable();
                $table->string('period', 10); // 'makki' | 'madani'
                $table->string('event_key')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('period');
                $table->index(['is_active', 'display_order']);
            });
        }

        if (! Schema::hasTable('quran_revelation_context_translations')) {
            Schema::create('quran_revelation_context_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('revelation_context_id')
                    ->constrained('quran_revelation_contexts', indexName: self::FK_CONTEXT)
                    ->cascadeOnDelete();
                $table->string('locale', 5)->index(self::IDX_LOCALE);
                $table->string('title', 500)->nullable();
                $table->longText('narrative')->nullable();
                $table->string('source_reference', 500)->nullable();
                $table->boolean('is_ready')->default(false);
                $table->timestamps();

                $table->unique(['revelation_context_id', 'locale'], self::UNIQUE_CONTEXT_LOCALE);
            });
        } else {
            $this->reconcilePartiallyCreatedTranslationsTable();
        }

        // FULLTEXT index for search (MySQL-specific; skipped on the SQLite test driver)
        if (config('database.default') === 'mysql' && ! $this->mysqlIndexExists(self::IDX_FULLTEXT)) {
            DB::statement(
                'ALTER TABLE quran_revelation_context_translations ADD FULLTEXT '.self::IDX_FULLTEXT.' (title, narrative)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_revelation_context_translations');
        Schema::dropIfExists('quran_revelation_contexts');
    }

    /**
     * Add the indexes/constraints the failed first run never reached.
     *
     * Only MySQL can be in this partial state (the original failure was
     * MySQL-specific); on other drivers the guarded create above always runs.
     */
    private function reconcilePartiallyCreatedTranslationsTable(): void
    {
        if (config('database.default') !== 'mysql') {
            return;
        }

        if (! $this->mysqlIndexExists(self::IDX_LOCALE)) {
            Schema::table('quran_revelation_context_translations', function (Blueprint $table) {
                $table->index('locale', self::IDX_LOCALE);
            });
        }

        if (! $this->mysqlIndexExists(self::UNIQUE_CONTEXT_LOCALE)) {
            Schema::table('quran_revelation_context_translations', function (Blueprint $table) {
                $table->unique(['revelation_context_id', 'locale'], self::UNIQUE_CONTEXT_LOCALE);
            });
        }

        if (! $this->mysqlForeignKeyExists(self::FK_CONTEXT)) {
            Schema::table('quran_revelation_context_translations', function (Blueprint $table) {
                $table->foreign('revelation_context_id', self::FK_CONTEXT)
                    ->references('id')
                    ->on('quran_revelation_contexts')
                    ->cascadeOnDelete();
            });
        }
    }

    private function mysqlIndexExists(string $indexName): bool
    {
        $rows = DB::select(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?
             LIMIT 1',
            ['quran_revelation_context_translations', $indexName]
        );

        return $rows !== [];
    }

    private function mysqlForeignKeyExists(string $constraintName): bool
    {
        $rows = DB::select(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
             LIMIT 1",
            ['quran_revelation_context_translations', $constraintName]
        );

        return $rows !== [];
    }
};
