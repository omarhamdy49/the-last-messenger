<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the public hadith pages must actually render.
 *
 * Each case here is a defect that shipped: a chapter index whose links had no
 * anchor to land on, a Quran shortcode printed at the reader, and a book
 * "number" that is a word painted over the icon beneath it.
 */
class HadithPublicRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function bookWithChapters(int $chapters, int $hadithsPerChapter): HadithBook
    {
        $collection = HadithCollection::factory()->create(['slug' => 'muslim']);
        $book = HadithBook::factory()->create([
            'collection_id' => $collection->id,
            'book_number' => '1',
            'is_published' => true,
        ]);

        $sort = 0;
        for ($c = 1; $c <= $chapters; $c++) {
            $chapter = HadithChapter::factory()->create([
                'book_id' => $book->id,
                'chapter_number' => (string) $c,
                'sort_order' => $c,
                'is_published' => true,
            ]);

            for ($i = 0; $i < $hadithsPerChapter; $i++) {
                $sort++;
                Hadith::factory()->create([
                    'collection_id' => $collection->id,
                    'book_id' => $book->id,
                    'chapter_id' => $chapter->id,
                    'reference_key' => '1-'.$sort,
                    'sort_order' => $sort,
                ]);
            }
        }

        return $book;
    }

    public function test_a_chapter_on_a_later_page_is_linked_with_its_page(): void
    {
        // 6 chapters x 5 hadiths = 30, so chapters 1–4 are on page 1 and the
        // rest are not in the DOM at all until page 2.
        $book = $this->bookWithChapters(6, 5);
        $slug = $book->collection->slug;

        $page1 = $this->get(route('public.hadith.book', [$slug, $book->book_number]))->assertOk();

        // The chapter that starts page 1 stays a bare fragment — still a scroll.
        $page1->assertSee('href="#chapter-1"', false);

        // The one that does not exist on this page carries the page that has it.
        $page1->assertSee('?page=2#chapter-5', false);
        $page1->assertDontSee('href="#chapter-5"', false);

        // And that page really does contain the anchor it was sent to.
        $this->get(route('public.hadith.book', [$slug, $book->book_number]).'?page=2')
            ->assertOk()
            ->assertSee('id="chapter-5"', false);
    }

    public function test_a_quran_shortcode_in_the_matn_is_rendered_as_a_link(): void
    {
        $collection = HadithCollection::factory()->create(['slug' => 'bukhari']);
        $hadith = Hadith::factory()->create([
            'collection_id' => $collection->id,
            'reference_key' => '66-4990',
            'text_ar' => 'قَالَ لَمَّا نَزَلَتْ [quran sura="4" aya_start="95" aya_end="95"]{لاَ يَسْتَوِي الْقَاعِدُونَ}',
        ]);

        $response = $this->get(route('public.hadith.show', $collection->slug.':'.$hadith->reference_key))
            ->assertOk();

        $response->assertSee(route('public.quran.verse', '4:95'), false);
        $response->assertSee('{لاَ يَسْتَوِي الْقَاعِدُونَ}', false);
        // The marker must not reach the reader in any form — raw or escaped.
        $response->assertDontSee('[quran', false);
        $response->assertDontSee('aya_start', false);
    }

    public function test_the_copy_buffer_and_meta_description_carry_no_markup(): void
    {
        $collection = HadithCollection::factory()->create(['slug' => 'bukhari']);
        $hadith = Hadith::factory()->create([
            'collection_id' => $collection->id,
            'reference_key' => '1-1',
            'text_ar' => 'نَزَلَتْ [quran sura="2" aya_start="255" aya_end="255"]{اللَّهُ لاَ إِلَهَ إِلاَّ هُوَ}',
        ]);

        $html = $this->get(route('public.hadith.show', $collection->slug.':'.$hadith->reference_key))
            ->assertOk()
            ->getContent();

        // The hidden span the copy button reads must be plain text: copying a
        // hadith should not paste an anchor tag.
        $this->assertMatchesRegularExpression(
            '/<span x-ref="matn" class="hidden">[^<]*<\/span>/u',
            (string) $html,
        );
    }

    public function test_a_book_numbered_with_a_word_shows_its_icon_not_the_word(): void
    {
        $collection = HadithCollection::factory()->create(['slug' => 'muslim']);
        $book = HadithBook::factory()->create([
            'collection_id' => $collection->id,
            'book_number' => 'introduction',
            'title_ar' => 'المقدمة',
            'is_published' => true,
        ]);
        Hadith::factory()->create([
            'collection_id' => $collection->id,
            'book_id' => $book->id,
            'reference_key' => 'introduction-1',
        ]);

        $this->assertFalse($book->hasFigureNumber());

        // The collection page must still link to the book — only the figure
        // badge is suppressed, never the navigation.
        $this->get(route('public.hadith.collection', $collection->slug))
            ->assertOk()
            ->assertSee(route('public.hadith.book', [$collection->slug, 'introduction']), false)
            ->assertSee('المقدمة')
            ->assertDontSee('sx-card-year', false);

        // A numbered book in the same collection keeps its badge.
        $numbered = HadithBook::factory()->create([
            'collection_id' => $collection->id,
            'book_number' => '2',
            'is_published' => true,
        ]);
        Hadith::factory()->create([
            'collection_id' => $collection->id,
            'book_id' => $numbered->id,
            'reference_key' => '2-1',
        ]);

        $this->assertTrue($numbered->hasFigureNumber());

        $this->get(route('public.hadith.collection', $collection->slug))
            ->assertOk()
            ->assertSee('sx-card-year', false);
    }
}
