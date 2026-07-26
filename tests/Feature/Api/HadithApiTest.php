<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Hadith\Models\HadithTranslation;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HadithApiTest extends TestCase
{
    use RefreshDatabase;

    private function createHadith(string $text = 'Actions are judged by intentions'): Hadith
    {
        $collection = HadithCollection::create([
            'name_ar' => 'صحيح البخاري',
            'name_en' => 'Sahih Bukhari',
            'slug_ar' => 'صحيح-البخاري',
            'slug_en' => 'sahih-bukhari',
            'abbreviation' => 'BUK',
        ]);

        $book = HadithBook::create([
            'collection_id' => $collection->id,
            'title_ar' => 'كتاب الوحي',
            'title_en' => 'Book of Revelation',
        ]);

        $hadith = Hadith::create([
            'book_id' => $book->id,
            'hadith_number' => '1',
            'grading_status' => Hadith::GRADING_SAHIH,
        ]);

        HadithTranslation::create([
            'hadith_id' => $hadith->id,
            'language_code' => 'en',
            'text' => $text,
        ]);

        return $hadith;
    }

    public function test_hadith_index_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/hadith');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_index_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith');

        $response->assertOk();
    }

    public function test_hadith_index_meta_contains_pagination_fields(): void
    {
        $response = $this->getJson('/api/v1/hadith');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_hadith_show_returns_404_for_nonexistent_id(): void
    {
        // No records seeded — any ID lookup must return 404.
        $response = $this->getJson('/api/v1/hadith/999999');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_show_404_has_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/hadith/999999');

        $response->assertNotFound()
            ->assertJsonStructure(['success', 'error' => ['message']]);
    }

    public function test_hadith_collections_returns_success(): void
    {
        $response = $this->getJson('/api/v1/hadith/collections');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_collections_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/collections');

        $response->assertOk();
    }

    public function test_hadith_collection_show_returns_404_for_nonexistent_id(): void
    {
        $response = $this->getJson('/api/v1/hadith/collections/999999');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_books_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/hadith/books');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_books_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/books');

        $response->assertOk();
    }

    public function test_hadith_book_show_returns_404_for_nonexistent_slug(): void
    {
        // Bug fixed: BookRepository::getBySlug() now validates Accept-Language header
        // against an allowlist before using it as a column name.
        $response = $this->getJson('/api/v1/hadith/books/non-existent-book-slug');

        $response->assertStatus(404);
    }

    public function test_hadith_narrators_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/hadith/narrators');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_narrators_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/narrators');

        $response->assertOk();
    }

    public function test_hadith_narrator_show_returns_404_for_nonexistent_id(): void
    {
        $response = $this->getJson('/api/v1/hadith/narrators/999999');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_search_requires_query_param(): void
    {
        $response = $this->getJson('/api/v1/hadith/search');

        $response->assertStatus(422);
    }

    public function test_hadith_search_requires_minimum_3_chars(): void
    {
        // HadithController.search validates q with min:3
        $response = $this->getJson('/api/v1/hadith/search?q=ab');

        $response->assertStatus(422);
    }

    public function test_hadith_search_accepts_valid_query(): void
    {
        $response = $this->getJson('/api/v1/hadith/search?q=test');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_search_accepts_three_char_minimum(): void
    {
        $response = $this->getJson('/api/v1/hadith/search?q=abc');

        $response->assertOk();
    }

    public function test_hadith_search_returns_matching_seeded_hadith_without_authentication(): void
    {
        $hadith = $this->createHadith();
        DB::commit(); // InnoDB FULLTEXT indexes expose committed rows only.

        $response = $this->getJson('/api/v1/hadith/search?q=intentions');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $hadith->id)
            ->assertJsonPath('data.0.translations.0.language_code', 'en')
            ->assertJsonPath('data.0.translations.0.text', 'Actions are judged by intentions')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'hadith_number', 'grading_status', 'translations'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_hadith_sahih_returns_success(): void
    {
        $response = $this->getJson('/api/v1/hadith/sahih');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_sahih_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/sahih');

        $response->assertOk();
    }

    public function test_hadith_reliable_returns_success(): void
    {
        $response = $this->getJson('/api/v1/hadith/reliable');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_reliable_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/reliable');

        $response->assertOk();
    }

    public function test_hadith_index_rejects_invalid_grade(): void
    {
        $response = $this->getJson('/api/v1/hadith?grade=invalid');

        $response->assertStatus(422);
    }

    public function test_hadith_index_accepts_valid_grade(): void
    {
        $response = $this->getJson('/api/v1/hadith?grade=sahih');

        $response->assertOk();
    }

    public function test_hadith_narrator_hadiths_returns_404_for_nonexistent_narrator(): void
    {
        $response = $this->getJson('/api/v1/hadith/narrators/999999/hadiths');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_by_theme_returns_404_for_nonexistent_theme(): void
    {
        $response = $this->getJson('/api/v1/hadith/by-theme/non-existent-theme');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_by_theme_returns_seeded_hadiths_by_human_readable_name_without_authentication(): void
    {
        $hadith = $this->createHadith();
        $theme = HadithTheme::create([
            'theme_ar' => 'الصبر',
            'theme_en' => 'Patience',
        ]);
        $hadith->themes()->attach($theme->id);

        $response = $this->getJson('/api/v1/hadith/by-theme/Patience', ['Accept-Language' => 'ar']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $hadith->id)
            ->assertJsonPath('data.0.themes.0.id', $theme->id)
            ->assertJsonPath('data.0.themes.0.theme_ar', 'الصبر')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'hadith_number', 'grading_status', 'translations', 'themes'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_hadith_by_theme_accepts_numeric_id(): void
    {
        $hadith = $this->createHadith();
        $theme = HadithTheme::create([
            'theme_ar' => 'النية',
            'theme_en' => 'Intention',
        ]);
        $hadith->themes()->attach($theme->id);

        $this->getJson("/api/v1/hadith/by-theme/{$theme->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $hadith->id);
    }

    public function test_hadith_by_theme_paginates_in_sql_and_caps_per_page_at_100(): void
    {
        $firstHadith = $this->createHadith('Hadith text 1');
        $theme = HadithTheme::create(['theme_ar' => 'موضوع كبير', 'theme_en' => 'Large Theme']);
        $hadithIds = [$firstHadith->id];

        for ($number = 2; $number <= 101; $number++) {
            $hadith = Hadith::create([
                'book_id' => $firstHadith->book_id,
                'hadith_number' => (string) $number,
                'grading_status' => Hadith::GRADING_SAHIH,
            ]);
            $hadith->translations()->create([
                'language_code' => 'en',
                'text' => "Hadith text {$number}",
            ]);
            $hadithIds[] = $hadith->id;
        }

        $theme->hadiths()->attach($hadithIds);
        $queries = [];
        DB::listen(static function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $response = $this->getJson("/api/v1/hadith/by-theme/{$theme->id}?per_page=500");

        $response->assertOk()
            ->assertJsonCount(100, 'data')
            ->assertJsonPath('meta.total', 101)
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);
        $this->assertTrue(
            collect($queries)->contains(
                fn (string $sql) => str_contains($sql, 'from `hadith_hadiths`')
                    && str_contains($sql, 'limit 100')
                    && ! str_contains($sql, '`hadith_hadiths`.*')
            ),
            'The hadith page must be explicitly selected and limited by SQL.'
        );
    }

    public function test_hadith_chapter_show_returns_404_for_nonexistent_chapter(): void
    {
        $response = $this->getJson('/api/v1/hadith/chapters/999999');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }
}
