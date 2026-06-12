<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Services\HadithQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the Hadith collection listing path.
 *
 * Guards three previously-broken behaviours:
 *  1. listCollections(onlyActive: true) crashed on a non-existent `is_active` column.
 *  2. ListCollectionsHandler discarded the mapped DTOs and returned raw Eloquent models.
 *  3. CollectionDTO::fromModel read `slug`/`hadith_count` instead of `slug_ar`/`total_hadiths`.
 * Plus the public collections API endpoint (HadithCollectionResource::getNameInLocale).
 */
class HadithCollectionListingTest extends TestCase
{
    use RefreshDatabase;

    private function seedCollection(array $overrides = []): HadithCollection
    {
        $collection = HadithCollection::create(array_merge([
            'name_ar' => 'صحيح البخاري',
            'name_en' => 'Sahih al-Bukhari',
            'slug_ar' => 'sahih-bukhari-ar',
            'slug_en' => 'sahih-bukhari-en',
            'description_ar' => 'أصح كتاب بعد كتاب الله',
            'description_en' => 'The most authentic book after the Quran',
            'abbreviation' => 'BUK',
            'canonical_code' => 'bukhari',
            'source_slug' => 'bukhari',
            'total_hadiths' => 7563,
        ], $overrides));

        HadithBook::create([
            'collection_id' => $collection->id,
            'book_number' => 1,
            'title_ar' => 'كتاب بدء الوحي',
            'title_en' => 'Revelation',
            'slug_ar' => 'bdء-alwhy',
            'slug_en' => 'revelation',
        ]);

        return $collection;
    }

    public function test_list_collections_with_only_active_does_not_throw(): void
    {
        $this->seedCollection();

        $service = app(HadithQueryService::class);

        // Before the fix this threw SQLSTATE[42S22] Unknown column 'is_active'.
        $result = $service->listCollections(perPage: 15, onlyActive: true, locale: 'ar');

        $this->assertSame(1, $result->total);
        $this->assertCount(1, $result->items);
    }

    public function test_list_collections_returns_dtos_not_models(): void
    {
        $this->seedCollection();

        $result = app(HadithQueryService::class)
            ->listCollections(perPage: 15, onlyActive: false, locale: 'ar');

        $this->assertInstanceOf(CollectionDTO::class, $result->items[0]);
    }

    public function test_collection_dto_maps_real_schema_columns(): void
    {
        $this->seedCollection([
            'slug_ar' => 'my-slug-ar',
            'slug_en' => 'my-slug-en',
            'total_hadiths' => 4242,
        ]);

        /** @var CollectionDTO $dto */
        $dto = app(HadithQueryService::class)
            ->listCollections(perPage: 15, onlyActive: true, locale: 'ar')
            ->items[0];

        // slug resolves from slug_ar (locale ar); hadithCount from total_hadiths.
        $this->assertSame('my-slug-ar', $dto->slug);
        $this->assertSame(4242, $dto->hadithCount);
        $this->assertSame(1, $dto->booksCount);
        $this->assertSame('صحيح البخاري', $dto->nameAr);
    }

    public function test_collection_dto_slug_follows_locale(): void
    {
        $this->seedCollection(['slug_ar' => 'ar-slug', 'slug_en' => 'en-slug']);

        $dto = app(HadithQueryService::class)
            ->listCollections(perPage: 15, onlyActive: false, locale: 'en')
            ->items[0];

        $this->assertSame('en-slug', $dto->slug);
    }

    public function test_collections_api_endpoint_returns_success_envelope(): void
    {
        $this->seedCollection();

        // Arabic is the canonical/default locale — pin it so locale-resolved fields are deterministic.
        app()->setLocale('ar');

        $response = $this->getJson('/api/v1/hadith/collections', ['Accept-Language' => 'ar']);

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'message'])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name_ar', 'صحيح البخاري')
            ->assertJsonPath('data.0.name_en', 'Sahih al-Bukhari')
            // total_hadiths + books_count are now actually selected/loaded (were null before the fix).
            ->assertJsonPath('data.0.total_hadiths', 7563)
            ->assertJsonPath('data.0.books_count', 1);
    }

    public function test_collections_api_endpoint_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/hadith/collections')->assertOk();
    }
}
