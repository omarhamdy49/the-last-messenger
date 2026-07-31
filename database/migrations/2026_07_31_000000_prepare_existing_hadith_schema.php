<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hadith_collections')) {
            $this->releaseArchivedForeignKeyNames();

            return;
        }

        if (! Schema::hasColumn('hadith_collections', 'code')) {
            $this->archiveLegacySchema();

            return;
        }

        $this->upgradePreviousRebuild();
    }

    public function down(): void
    {
        // The archived schema is intentionally retained for recovery.
    }

    private function archiveLegacySchema(): void
    {
        $tables = [
            'hadith_gradings',
            'hadith_grading_levels',
            'hadith_translations',
            'hadith_hadiths',
            'hadith_chapters',
            'hadith_books',
            'hadith_scholars',
            'hadith_collections',
        ];

        foreach ($tables as $table) {
            $archive = 'legacy_'.$table;

            if (Schema::hasTable($table) && ! Schema::hasTable($archive)) {
                Schema::rename($table, $archive);
            }
        }

        $this->releaseArchivedForeignKeyNames();
    }

    private function upgradePreviousRebuild(): void
    {
        if (! Schema::hasColumn('hadith_books', 'is_synthetic')) {
            Schema::table('hadith_books', function (Blueprint $table): void {
                $table->boolean('is_synthetic')->default(false)->after('last_hadith_number');
            });
        }

        if (! Schema::hasColumn('hadith_hadiths', 'display_grade')) {
            Schema::table('hadith_hadiths', function (Blueprint $table): void {
                $table->enum('display_grade', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'])
                    ->default('unknown')->after('sunnah_urn_en');
            });

            if (Schema::hasColumn('hadith_hadiths', 'grade')) {
                DB::table('hadith_hadiths')->update(['display_grade' => DB::raw('grade')]);
            }
        }

        if (Schema::hasColumn('hadith_hadiths', 'text_hash')) {
            Schema::table('hadith_hadiths', function (Blueprint $table): void {
                $table->char('text_hash', 64)->nullable()->change();
            });
        }

        if (! Schema::hasColumn('hadith_texts', 'search_body')) {
            Schema::table('hadith_texts', function (Blueprint $table): void {
                $table->longText('search_body')->nullable()->after('body');
            });

            $source = Schema::hasColumn('hadith_texts', 'body_normalized')
                ? 'COALESCE(body_normalized, body)'
                : 'body';

            DB::table('hadith_texts')->update(['search_body' => DB::raw($source)]);
        }

        if (! Schema::hasColumn('hadith_texts', 'narrated_by')) {
            Schema::table('hadith_texts', function (Blueprint $table): void {
                $table->string('narrated_by', 500)->nullable()->after('search_body');
            });
        }

        if (DB::getDriverName() === 'mysql' && ! $this->indexExists('hadith_texts', 'ft_hadith_texts_search')) {
            DB::statement('ALTER TABLE hadith_texts ADD FULLTEXT ft_hadith_texts_search (search_body)');
        }

        Schema::table('hadith_gradings', function (Blueprint $table): void {
            if (! Schema::hasColumn('hadith_gradings', 'part_label')) {
                $table->string('part_label', 100)->nullable()->after('scope');
            }
            if (! Schema::hasColumn('hadith_gradings', 'authority_rank')) {
                $table->unsignedTinyInteger('authority_rank')->default(100)->after('part_label');
            }
            if (Schema::hasColumn('hadith_gradings', 'raw_grade')) {
                $table->text('raw_grade')->change();
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    private function dropForeignKeys(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $keys = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->pluck('CONSTRAINT_NAME');

        foreach ($keys as $key) {
            Schema::table($table, function (Blueprint $blueprint) use ($key): void {
                $blueprint->dropForeign((string) $key);
            });
        }
    }

    private function releaseArchivedForeignKeyNames(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('legacy_hadith_collections')) {
            return;
        }

        foreach ([
            'legacy_hadith_gradings',
            'legacy_hadith_grading_levels',
            'legacy_hadith_translations',
            'legacy_hadith_hadiths',
            'legacy_hadith_chapters',
            'legacy_hadith_books',
            'legacy_hadith_scholars',
            'legacy_hadith_collections',
        ] as $table) {
            $this->dropForeignKeys($table);
        }
    }
};
