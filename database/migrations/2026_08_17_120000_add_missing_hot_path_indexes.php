<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the hot read paths, plus removal of four redundant duplicates.
 *
 * Every entry here was verified against the LIVE production schema
 * (information_schema.STATISTICS), not against the migration history — this codebase has
 * had real schema drift, so the migrations are not a reliable description of the database.
 *
 * ADDED
 *   articles(status, created_at)          Every public listing is
 *                                        `WHERE status='published' ORDER BY created_at DESC`.
 *                                        Only a single-column `status` index existed, so the
 *                                        ordering filesorted.
 *   articles(status, views)               Backs the popular/most-viewed listings.
 *   media_programs(is_published, created_at)
 *                                        `(is_published, publish_at)` exists, but
 *                                        MediaProgramRepository::getRecent() orders by
 *                                        created_at — the wrong column for that index, and
 *                                        getRecent() is the one row-builder with no cache.
 *   quran_verse_tafsirs FULLTEXT(text)    removed by 006-quran-qf-refactor; quran_tafsirs
 *                                        (2026_09_18_000006) declares its own FULLTEXT.
 *   search_history(query(64))             Prefix index — `query` is too long to index whole.
 *   user_interactions(interaction_type, created_at)
 *                                        Analytics filter by type over a window.
 *
 * DROPPED — four indexes doing the work of two, on tables that take writes
 *   articles.idx_articles_status          duplicate of articles_status_index(status)
 *   articles.articles_slug_index          duplicate of articles_slug_unique(slug); the
 *                                        UNIQUE already serves lookups, so the plain index
 *                                        only added write amplification
 *
 * Guarded with hasIndex()/hasColumn() throughout so this is safe to run against a database
 * that has drifted, and safe to re-run.
 */
return new class extends Migration
{
    /**
     * @var array<int, array{table: string, name: string, columns: array<int, string>, type?: string}>
     */
    private array $additions = [
        ['table' => 'articles', 'name' => 'articles_status_created_at_index', 'columns' => ['status', 'created_at']],
        ['table' => 'articles', 'name' => 'articles_status_views_index', 'columns' => ['status', 'views']],
        ['table' => 'media_programs', 'name' => 'media_programs_is_published_created_at_index', 'columns' => ['is_published', 'created_at']],
        ['table' => 'user_interactions', 'name' => 'user_interactions_type_created_at_index', 'columns' => ['interaction_type', 'created_at']],
    ];

    /**
     * @var array<int, array{table: string, name: string}>
     */
    private array $redundant = [
        ['table' => 'articles', 'name' => 'idx_articles_status'],
        ['table' => 'articles', 'name' => 'articles_slug_index'],
    ];

    public function up(): void
    {
        foreach ($this->additions as $index) {
            $this->addIndex($index['table'], $index['name'], $index['columns']);
        }

        // Prefix index: `search_history.query` is a long free-text column, so index the
        // leading 64 characters rather than the whole value. (MySQL only - SQLite doesn't support prefix indexes)
        if (Schema::getConnection()->getDriverName() === 'mysql'
            && Schema::hasTable('search_history')
            && Schema::hasColumn('search_history', 'query')
            && ! $this->indexExists('search_history', 'search_history_query_prefix_index')) {
            Schema::getConnection()->statement(
                'ALTER TABLE `search_history` ADD INDEX `search_history_query_prefix_index` (`query`(64))'
            );
        }

        foreach ($this->redundant as $index) {
            $this->dropIndex($index['table'], $index['name']);
        }
    }

    public function down(): void
    {
        foreach ($this->additions as $index) {
            $this->dropIndex($index['table'], $index['name']);
        }

        $this->dropIndex('search_history', 'search_history_query_prefix_index');

        // Recreate the duplicates dropped in up(), so this migration is reversible even
        // though restoring them is a pessimisation.
        $this->addIndex('articles', 'idx_articles_status', ['status']);
        $this->addIndex('articles', 'articles_slug_index', ['slug']);
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndex(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $name)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropIndex($name);
        });
    }

    /**
     * Schema::hasIndex() is driver-dependent for FULLTEXT and prefix indexes, so ask
     * information_schema directly — it reports every index type uniformly.
     */
    private function indexExists(string $table, string $name): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return Schema::hasIndex($table, $name);
        }

        return $connection->table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $connection->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $name)
            ->exists();
    }
};
