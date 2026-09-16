<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Services\HadithBrowseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HadithCollectionBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookless_collections_list_their_hadiths_with_pagination(): void
    {
        foreach (['nawawi40', 'qudsi40', 'shahwaliullah40'] as $slug) {
            $collection = HadithCollection::factory()->bookless()->create(['slug' => $slug]);
            for ($i = 1; $i <= 21; $i++) {
                Hadith::factory()->create([
                    'collection_id' => $collection->id, 'book_id' => null,
                    'reference_key' => '1-'.$i, 'sort_order' => $i,
                    'text_ar' => 'نص ظاهر '.$slug.' رقم '.$i,
                ]);
            }
            Hadith::factory()->unpublished()->create([
                'collection_id' => $collection->id, 'text_ar' => 'نص مخفي',
            ]);

            $this->get(route('public.hadith.collection', $slug))
                ->assertOk()
                ->assertSee('نص ظاهر '.$slug.' رقم 1')
                ->assertSee('/hadith/'.$slug.':1-1')
                ->assertDontSee('نص ظاهر '.$slug.' رقم 21')
                ->assertDontSee('نص مخفي')
                ->assertDontSee(__('hadith.no_books'));

            $this->get(route('public.hadith.collection', ['slug' => $slug, 'page' => 2]))
                ->assertOk()->assertSee('نص ظاهر '.$slug.' رقم 21');
        }
    }

    public function test_structured_collections_keep_the_book_index(): void
    {
        $collection = HadithCollection::factory()->create(['has_books' => true]);
        $book = HadithBook::factory()->create(['collection_id' => $collection->id]);

        $this->get(route('public.hadith.collection', $collection->slug))
            ->assertOk()->assertSee($book->title_ar)
            ->assertSee(route('public.hadith.book', [$collection->slug, $book->book_number]));
    }

    public function test_an_unpublished_bookless_collection_is_not_visible(): void
    {
        $collection = HadithCollection::factory()->bookless()->create(['is_published' => false]);
        Hadith::factory()->create(['collection_id' => $collection->id]);

        $this->get(route('public.hadith.collection', $collection->slug))->assertNotFound();
    }

    public function test_admin_edits_invalidate_cached_collection_and_hadith_totals(): void
    {
        $browse = app(HadithBrowseService::class);
        $this->assertCount(0, $browse->collections());
        $this->assertSame(0, $browse->totalVisible());
        $collection = HadithCollection::factory()->bookless()->create();
        $hadith = Hadith::factory()->create(['collection_id' => $collection->id]);

        $this->assertCount(1, $browse->collections());
        $this->assertSame(1, $browse->totalVisible());
        $hadith->update(['is_published' => false]);
        $this->assertSame(0, $browse->totalVisible());
        $collection->update(['is_published' => false]);
        $this->assertCount(0, $browse->collections());
    }
}
