<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Domain\Shared\Enums\SearchModule;
use App\Domain\Shared\Search\SearchQueryBuilder;
use App\Models\Article;
use App\Models\ArticleLocale;
use Tests\Support\CommitsForFullTextSearch;
use Tests\TestCase;

/**
 * Unified search must use the FULLTEXT indexes that exist.
 *
 * SearchQueryBuilder::applySearch() previously gated on
 * `method_exists($modelClass, 'getFullTextColumns')`, which no model implemented — so every
 * search took the OR-LIKE path: four modules × (COUNT + SELECT) with correlated
 * `orWhereHas` LIKEs over `article_locale` and `quran_verse_translations` (112k rows in
 * production), while every FULLTEXT index in the schema went unused.
 *
 * The gate could never have fired. MATCH() requires an index covering exactly the listed
 * columns and cannot span tables, and for every content module the searchable text lives in
 * a per-locale translation table — so the MATCH has to be inside the relationship subquery.
 */
class FullTextSearchTest extends TestCase
{
    /**
     * DatabaseTruncation, NOT RefreshDatabase — this is a hard requirement, not a
     * preference. InnoDB does not update a FULLTEXT index until the inserting transaction
     * COMMITS, and RefreshDatabase wraps each test in a transaction it rolls back. Under it,
     * MATCH() against rows the test just inserted returns nothing: the query is syntactically
     * fine and silently finds zero rows, so the test fails for a reason that has nothing to
     * do with the code under test. Any future FULLTEXT test needs the same treatment.
     */
    use CommitsForFullTextSearch;

    private function builder(): SearchQueryBuilder
    {
        return app(SearchQueryBuilder::class);
    }

    /**
     * The important one: proves MySQL accepts the generated MATCH inside a whereHas
     * subquery and that it actually matches. A syntax error or a wrong table reference
     * would surface here, not in a toSql() assertion.
     */
    public function test_article_search_matches_via_the_translation_table_fulltext_index(): void
    {
        $wanted = Article::factory()->create(['slug' => 'ft-wanted', 'status' => 'published']);
        ArticleLocale::create([
            'article_id' => $wanted->id,
            'locale' => 'ar',
            'title' => 'غزوة بدر الكبرى',
            'slug' => 'ft-wanted',
            'excerpt' => 'مقدمة عن الغزوة',
            'is_ready' => true,
        ]);

        $other = Article::factory()->create(['slug' => 'ft-other', 'status' => 'published']);
        ArticleLocale::create([
            'article_id' => $other->id,
            'locale' => 'ar',
            'title' => 'فتح مكة',
            'slug' => 'ft-other',
            'excerpt' => 'موضوع مختلف',
            'is_ready' => true,
        ]);

        $query = Article::query();
        $this->builder()->applySearch($query, SearchModule::ARTICLE, 'بدر');

        $ids = $query->pluck('id')->all();

        $this->assertContains($wanted->id, $ids, 'FULLTEXT search did not find the matching article');
        $this->assertNotContains($other->id, $ids, 'FULLTEXT search returned an unrelated article');
    }

    /**
     * article_locale's ft_search covers (title, excerpt) only, so `content` must stay on
     * LIKE — otherwise narrowing index coverage silently drops a field from search.
     */
    public function test_article_content_is_still_searchable_even_though_no_index_covers_it(): void
    {
        $article = Article::factory()->create(['slug' => 'ft-content', 'status' => 'published']);
        ArticleLocale::create([
            'article_id' => $article->id,
            'locale' => 'ar',
            'title' => 'عنوان لا صلة له',
            'slug' => 'ft-content',
            'excerpt' => 'مقدمة',
            'content' => 'كلمة نادرة داخل المتن وهي زمزم',
            'is_ready' => true,
        ]);

        $query = Article::query();
        $this->builder()->applySearch($query, SearchModule::ARTICLE, 'زمزم');

        $this->assertContains($article->id, $query->pluck('id')->all());
    }

    /**
     * Each module must generate MATCH for every FULLTEXT group it declares. `muslims` has
     * two SEPARATE single-column indexes, so it must emit two MATCH clauses — combining
     * them into MATCH(search_name_ar, search_name_en) is a MySQL error.
     */
    public function test_each_module_generates_the_expected_number_of_match_clauses(): void
    {
        $expected = [
            SearchModule::ARTICLE->value => 1,
            SearchModule::MEDIA->value => 1,
            SearchModule::SEERAH->value => 1,
            // Quran has two indexes now: the verse scripts and the translation text.
            SearchModule::QURAN->value => 2,
            SearchModule::SAHABA->value => 2,
        ];

        foreach ($expected as $moduleValue => $matchCount) {
            $module = SearchModule::from($moduleValue);
            $modelClass = $module->modelClass();
            $this->assertNotNull($modelClass);

            $query = $modelClass::query();
            $this->builder()->applySearch($query, $module, 'الصلاة');

            $this->assertSame(
                $matchCount,
                substr_count(strtolower($query->toSql()), 'match('),
                "[{$moduleValue}] emitted the wrong number of MATCH clauses"
            );
        }
    }

    /**
     * Below MySQL's minimum token length FULLTEXT returns nothing, so
     * FullTextSearchHelper falls back to LIKE. Without that a two-character query would
     * silently return an empty result set.
     */
    public function test_short_terms_fall_back_to_like(): void
    {
        $query = Article::query();
        $this->builder()->applySearch($query, SearchModule::ARTICLE, 'ab');

        $sql = strtolower($query->toSql());
        $this->assertStringNotContainsString('match(', $sql);
        $this->assertStringContainsString('like', $sql);
    }

    public function test_an_empty_term_adds_no_conditions(): void
    {
        $query = Article::query();
        $before = $query->toSql();

        $this->builder()->applySearch($query, SearchModule::ARTICLE, '   ');

        $this->assertSame($before, $query->toSql());
    }
}
