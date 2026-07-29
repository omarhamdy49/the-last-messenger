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
            'code' => 'bukhari',
            'name' => ['ar' => 'صحيح البخاري', 'en' => 'Sahih al-Bukhari'],
            'slug' => 'sahih-bukhari',
            'description' => [
                'ar' => 'أصح كتاب بعد كتاب الله',
                'en' => 'The most authentic book after the Quran',
            ],
            'abbreviation' => 'BUK',
        ], $overrides));

        HadithBook::create([
            'collection_id' => $collection->id,
            'book_number' => 1,
            'title' => ['ar' => 'كتاب بدء الوحي', 'en' => 'Revelation'],
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
            'slug' => 'my-slug',
        ]);

        /** @var CollectionDTO $dto */
        $dto = app(HadithQueryService::class)
            ->listCollections(perPage: 15, onlyActive: true, locale: 'ar')
            ->items[0];

        $this->assertSame('my-slug', $dto->slug);
        $this->assertSame(0, $dto->hadithCount);
        $this->assertSame(1, $dto->booksCount);
        $this->assertSame('صحيح البخاري', $dto->nameAr);
    }

    public function test_collection_dto_uses_one_canonical_slug_for_every_locale(): void
    {
        $this->seedCollection(['slug' => 'canonical-slug']);

        $dto = app(HadithQueryService::class)
            ->listCollections(perPage: 15, onlyActive: false, locale: 'en')
            ->items[0];

        $this->assertSame('canonical-slug', $dto->slug);
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
            ->assertJsonPath('data.0.books_count', 1);
    }

    public function test_collections_api_endpoint_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/hadith/collections')->assertOk();
    }
}
