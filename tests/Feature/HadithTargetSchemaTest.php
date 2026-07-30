<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Hadith\Repositories\ChapterRepositoryInterface;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Hadith\Repositories\NarratorRepositoryInterface;
use App\Domain\Hadith\Repositories\ScholarRepositoryInterface;
use App\Domain\Hadith\Repositories\ThemeRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HadithTargetSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_target_hadith_schema_exists_with_mysql_constraints(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL SHOW CREATE TABLE verification.');
        }

        $targetTables = [
            'hadith_books',
            'hadith_chapters',
            'hadith_collections',
            'hadith_commentaries',
            'hadith_applications',
            'hadith_fiqh_rulings',
            'hadith_gradings',
            'hadith_hadith_themes',
            'hadith_hadiths',
            'hadith_isnad',
            'hadith_keywords',
            'hadith_narrators',
            'hadith_related_verses',
            'hadith_scholars',
            'hadith_sources',
            'hadith_sync_logs',
            'hadith_themes',
            'hadith_translations',
        ];

        $droppedTables = [
            'hadith_chapter_translations',
            'hadith_companions',
            'hadith_grading_levels',
            'hadith_source_translations',
            'hadith_theme_translations',
        ];

        foreach ($targetTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} is missing");
        }

        foreach ($droppedTables as $table) {
            $this->assertFalse(Schema::hasTable($table), "{$table} still exists");
        }

        $this->assertTrue(Schema::hasColumn('hadith_translations', 'search_text'));
        $this->assertFalse(Schema::hasColumn('hadith_translations', 'language_code'));
        $this->assertFalse(Schema::hasColumn('hadith_hadiths', 'book_id'));
        $this->assertFalse(Schema::hasColumn('hadith_themes', 'parent_id'));

        $definitions = [];
        foreach ($targetTables as $table) {
            $row = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $definitions[$table] = strtolower((string) array_values($row)[1]);
        }

        $expectedFragments = [
            'hadith_collections' => [
                'unique key `hadith_collections_code_unique` (`code`)',
                'unique key `hadith_collections_slug_unique` (`slug`)',
                'key `idx_hadith_collections_public_order` (`active`,`featured`,`display_order`)',
                'constraint `chk_hadith_collections_code_lower` check',
            ],
            'hadith_books' => [
                'unique key `hadith_books_collection_id_book_number_unique` (`collection_id`,`book_number`)',
                'on delete restrict',
            ],
            'hadith_chapters' => [
                'unique key `hadith_chapters_book_id_chapter_number_unique` (`book_id`,`chapter_number`)',
                'on delete restrict',
            ],
            'hadith_hadiths' => [
                'key `hadith_hadiths_chapter_id_hadith_number_index` (`chapter_id`,`hadith_number`)',
                'key `hadith_hadiths_text_hash_index` (`text_hash`)',
                'on delete restrict',
            ],
            'hadith_translations' => [
                'unique key `hadith_translations_hadith_id_locale_unique` (`hadith_id`,`locale`)',
                'fulltext key `ft_hadith_translations_text` (`text`)',
                'on delete cascade',
            ],
            'hadith_isnad' => [
                'unique key `hadith_isnad_hadith_id_position_in_chain_unique` (`hadith_id`,`position_in_chain`)',
                'unique key `hadith_isnad_hadith_id_narrator_id_unique` (`hadith_id`,`narrator_id`)',
                'constraint `chk_hadith_isnad_position_positive` check',
            ],
            'hadith_gradings' => [
                'key `hadith_gradings_grade_key_hadith_id_index` (`grade_key`,`hadith_id`)',
                'key `hadith_gradings_hadith_id_scholar_id_index` (`hadith_id`,`scholar_id`)',
            ],
            'hadith_commentaries' => [
                'key `hadith_commentaries_hadith_id_locale_index` (`hadith_id`,`locale`)',
            ],
            'hadith_hadith_themes' => [
                'primary key (`hadith_id`,`hadith_theme_id`)',
                'key `hadith_hadith_themes_hadith_theme_id_hadith_id_index` (`hadith_theme_id`,`hadith_id`)',
            ],
            'hadith_keywords' => [
                'unique key `hadith_keywords_hadith_id_locale_keyword_unique` (`hadith_id`,`locale`,`keyword`)',
                'key `hadith_keywords_locale_keyword_hadith_id_index` (`locale`,`keyword`,`hadith_id`)',
            ],
            'hadith_sources' => [
                'unique key `hadith_sources_source_system_external_id_unique` (`source_system`,`external_id`)',
            ],
            'hadith_related_verses' => [
                'unique key `hadith_related_verses_hadith_id_quran_verse_id_unique` (`hadith_id`,`quran_verse_id`)',
                'on delete restrict',
            ],
            'hadith_applications' => [
                'key `idx_hadith_applications_order` (`hadith_id`,`display_order`)',
                'constraint `chk_hadith_applications_review_lower` check',
                'on delete cascade',
            ],
            'hadith_fiqh_rulings' => [
                'key `idx_hadith_fiqh_rulings_order` (`hadith_id`,`type`,`display_order`)',
                'constraint `chk_hadith_fiqh_rulings_type_lower` check',
                'on delete restrict',
            ],
        ];

        foreach ($expectedFragments as $table => $fragments) {
            foreach ($fragments as $fragment) {
                $this->assertStringContainsString($fragment, $definitions[$table], "{$table}: {$fragment}");
            }
        }
    }

    public function test_touched_repositories_execute_against_mysql_without_missing_schema_errors(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL repository smoke verification.');
        }

        app(HadithRepositoryInterface::class)->paginateWithFilters();
        app(CollectionRepositoryInterface::class)->paginateWithFilters();
        app(ChapterRepositoryInterface::class)->getByBook(0);
        app(ThemeRepositoryInterface::class)->paginateWithFilters();
        app(NarratorRepositoryInterface::class)->paginateWithFilters();
        app(ScholarRepositoryInterface::class)->getAllByEra();

        $this->assertTrue(true);
    }
}
