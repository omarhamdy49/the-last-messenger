<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Hadith\Models\HadithText;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Models\SahabiTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HadithDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private HadithCollection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->collection = HadithCollection::factory()->create([
            'code' => 'bukhari',
            'name' => ['ar' => 'صحيح البخاري', 'en' => 'Sahih al-Bukhari'],
            'reference_template' => 'Sahih al-Bukhari {hadithNumber}',
            'has_volumes' => true,
        ]);
    }

    private function makeHadith(array $overrides = []): Hadith
    {
        $book = HadithBook::factory()->for($this->collection, 'collection')->create([
            'book_number' => '1',
            'title' => ['ar' => 'كتاب بدء الوحى', 'en' => 'Revelation'],
        ]);
        $chapter = HadithChapter::factory()->for($book, 'book')->create(['chapter_number' => '1']);

        return Hadith::query()->create(array_merge([
            'collection_id' => $this->collection->id,
            'book_id' => $book->id,
            'chapter_id' => $chapter->id,
            'hadith_number' => '1',
            'in_book_number' => 1,
            'volume_number' => '1',
            'sunnah_urn' => 10,
            'is_published' => true,
        ], $overrides));
    }

    public function test_the_canonical_route_renders_the_three_reference_lines(): void
    {
        $hadith = $this->makeHadith();
        HadithText::factory()->for($hadith, 'hadith')->arabic('إِنَّمَا الأَعْمَالُ بِالنِّيَّاتِ')->create();
        HadithText::factory()->for($hadith, 'hadith')->english('Actions are judged by intentions')->create();

        $response = $this->get('/hadith/bukhari:1');

        $response->assertOk();
        $response->assertSee('Sahih al-Bukhari 1');
        $response->assertSee('Book 1, Hadith 1');
        $response->assertSee('Vol. 1, Book 1, Hadith 1');
        // bukhari is one of the three collections carrying the deprecation note.
        $response->assertSee(__('hadith.reference_deprecated_note'));
    }

    public function test_it_renders_both_locales(): void
    {
        $hadith = $this->makeHadith();
        HadithText::factory()->for($hadith, 'hadith')->arabic('إِنَّمَا الأَعْمَالُ بِالنِّيَّاتِ')->create();
        HadithText::factory()->for($hadith, 'hadith')->english('Actions are judged by intentions')->create();

        $response = $this->get('/hadith/bukhari:1');

        $response->assertSee('إِنَّمَا الأَعْمَالُ بِالنِّيَّاتِ', false);
        $response->assertSee('Actions are judged by intentions');
    }

    public function test_an_arabic_only_hadith_states_the_translation_is_missing(): void
    {
        $hadith = $this->makeHadith();
        HadithText::factory()->for($hadith, 'hadith')->arabic()->create();

        $this->get('/hadith/bukhari:1')
            ->assertOk()
            ->assertSee(__('hadith.no_translation'));
    }

    public function test_an_unknown_number_is_a_404(): void
    {
        $this->makeHadith();

        $this->get('/hadith/bukhari:9999')->assertNotFound();
    }

    public function test_an_unknown_collection_is_a_404(): void
    {
        $this->get('/hadith/nosuch:1')->assertNotFound();
    }

    /** Unpublished must 404, never render an empty page. */
    public function test_an_unpublished_hadith_is_a_404(): void
    {
        $hadith = $this->makeHadith(['is_published' => false]);
        HadithText::factory()->for($hadith, 'hadith')->arabic()->create();

        $this->get('/hadith/bukhari:1')->assertNotFound();
    }

    public function test_a_hadith_in_an_unpublished_collection_is_a_404(): void
    {
        $hadith = $this->makeHadith();
        HadithText::factory()->for($hadith, 'hadith')->arabic()->create();
        $this->collection->update(['is_published' => false]);

        $this->get('/hadith/bukhari:1')->assertNotFound();
    }

    public function test_a_non_numeric_hadith_number_resolves(): void
    {
        $hadith = $this->makeHadith(['hadith_number' => '1a']);
        HadithText::factory()->for($hadith, 'hadith')->arabic()->create();

        $this->get('/hadith/bukhari:1a')->assertOk();
    }

    public function test_the_permalink_route_resolves_a_urn(): void
    {
        $hadith = $this->makeHadith();
        HadithText::factory()->for($hadith, 'hadith')->arabic()->create();

        $this->get('/hadith/urn/10')->assertOk()->assertSee('Sahih al-Bukhari 1');
    }

    public function test_an_unknown_urn_is_a_clean_404(): void
    {
        $this->get('/hadith/urn/999999')->assertNotFound();
    }

    public function test_a_primary_narrator_links_the_hadith_profile_and_sahaba_profile(): void
    {
        $sahabi = Sahabi::query()->create(['slug' => 'umar-ibn-al-khattab']);
        SahabiTranslation::query()->create([
            'sahabi_id' => $sahabi->id,
            'locale' => 'en',
            'name' => 'Umar ibn al-Khattab',
            'is_ready' => true,
        ]);
        $narrator = HadithScholar::factory()->companion()->create([
            'slug' => 'umar-ibn-al-khattab',
            'name' => ['ar' => 'عمر بن الخطاب', 'en' => 'Umar ibn al-Khattab'],
            'full_name' => ['en' => 'Umar ibn al-Khattab ibn Nufayl'],
            'biography' => ['en' => 'A Companion and narrator of hadith.'],
            'sahaba_id' => $sahabi->id,
        ]);
        $hadith = $this->makeHadith(['primary_narrator_id' => $narrator->id]);
        HadithText::factory()->for($hadith, 'hadith')->english('Actions are judged by intentions')->create();

        $this->get('/hadith/bukhari:1?lang=en')
            ->assertOk()
            ->assertSee('Umar ibn al-Khattab')
            ->assertSee(route('public.hadith.narrators.show', $narrator->slug), false);

        $this->get(route('public.hadith.narrators.show', $narrator->slug))
            ->assertOk()
            ->assertSee('A Companion and narrator of hadith.')
            ->assertSee(route('public.hadith.show', ['collection' => 'bukhari', 'number' => '1']), false)
            ->assertSee(route('public.sahaba.show', ['slug' => $sahabi->id]), false);

        $this->get(route('public.sahaba.show', ['slug' => $sahabi->id]))->assertOk();
    }
}
