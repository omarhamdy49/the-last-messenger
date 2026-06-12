<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Quran\Models\RevelationContext;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RevelationContextPublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_asbab_page_returns_200_and_lists_active_contexts(): void
    {
        $surah = $this->createSurah(108, 'الكوثر', 'Al-Kawthar', 'makkah');
        $this->createVerseWithArabicText($surah, 1, 'إِنَّا أَعْطَيْنَاكَ الْكَوْثَرَ');

        $this->createContext($surah, [
            'verse_from' => 1,
            'verse_to' => 3,
            'period' => 'makki',
        ], [
            'title' => 'الرد على العاص بن وائل',
            'narrative' => 'لما مات ابن النبي قال العاص بن وائل إنه أبتر.',
            'source_reference' => 'أسباب النزول للواحدي',
        ]);

        $response = $this->get(route('public.quran.asbab'));

        $response->assertOk()
            ->assertSee('الرد على العاص بن وائل')
            ->assertSee('إِنَّا أَعْطَيْنَاكَ الْكَوْثَرَ')
            ->assertSee('أسباب النزول للواحدي');
    }

    public function test_inactive_contexts_are_hidden_from_the_public_page(): void
    {
        $surah = $this->createSurah(108, 'الكوثر', 'Al-Kawthar', 'makkah');

        $this->createContext($surah, ['is_active' => false], [
            'title' => 'سبب نزول غير منشور',
            'narrative' => 'محتوى غير منشور.',
        ]);

        $this->get(route('public.quran.asbab'))
            ->assertOk()
            ->assertDontSee('سبب نزول غير منشور');
    }

    public function test_period_filter_limits_results(): void
    {
        $makkiSurah = $this->createSurah(108, 'الكوثر', 'Al-Kawthar', 'makkah');
        $madaniSurah = $this->createSurah(24, 'النور', 'An-Nur', 'madinah');

        $this->createContext($makkiSurah, ['period' => 'makki'], [
            'title' => 'حدث مكي للاختبار',
            'narrative' => 'سياق مكي.',
        ]);
        $this->createContext($madaniSurah, ['period' => 'madani'], [
            'title' => 'حدث مدني للاختبار',
            'narrative' => 'سياق مدني.',
        ]);

        $this->get(route('public.quran.asbab', ['period' => 'makki']))
            ->assertOk()
            ->assertSee('حدث مكي للاختبار')
            ->assertDontSee('حدث مدني للاختبار');
    }

    public function test_search_filter_matches_title_and_narrative(): void
    {
        $surah = $this->createSurah(58, 'المجادلة', 'Al-Mujadila', 'madinah');

        $this->createContext($surah, ['period' => 'madani'], [
            'title' => 'ظهار خولة بنت ثعلبة',
            'narrative' => 'جاءت تشتكي إلى النبي.',
        ]);
        $this->createContext($surah, ['period' => 'madani', 'verse_from' => 5], [
            'title' => 'حدث آخر لا يطابق',
            'narrative' => 'سياق مختلف تماماً.',
        ]);

        $this->get(route('public.quran.asbab', ['q' => 'خولة']))
            ->assertOk()
            ->assertSee('ظهار خولة بنت ثعلبة')
            ->assertDontSee('حدث آخر لا يطابق');
    }

    public function test_surah_filter_limits_results(): void
    {
        $kawthar = $this->createSurah(108, 'الكوثر', 'Al-Kawthar', 'makkah');
        $nur = $this->createSurah(24, 'النور', 'An-Nur', 'madinah');

        $this->createContext($kawthar, [], ['title' => 'حدث الكوثر', 'narrative' => 'سياق.']);
        $this->createContext($nur, ['period' => 'madani'], ['title' => 'حدث النور', 'narrative' => 'سياق.']);

        $this->get(route('public.quran.asbab', ['surah' => $kawthar->id]))
            ->assertOk()
            ->assertSee('حدث الكوثر')
            ->assertDontSee('حدث النور');
    }

    public function test_empty_state_renders_with_reset_link(): void
    {
        $this->get(route('public.quran.asbab'))
            ->assertOk()
            ->assertSee(route('public.quran.asbab'));
    }

    private function createSurah(int $number, string $nameAr, string $nameEn, string $place): Surah
    {
        return Surah::query()->create([
            'revelation_place' => $place,
            'revelation_order' => $number,
            'bismillah_pre' => true,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'transliteration' => $nameEn,
            'verses_count' => 10,
        ]);
    }

    private function createVerseWithArabicText(Surah $surah, int $verseNumber, string $arabicText): Verse
    {
        $verse = Verse::query()->create([
            'surah_id' => $surah->id,
            'verse_number' => $verseNumber,
            'verse_key' => $surah->revelation_order.':'.$verseNumber,
            'juz_number' => 30,
            'hizb_number' => 60,
            'rub_el_hizb_number' => 240,
            'ruku_number' => 1,
            'page_number' => 602,
        ]);

        VerseTranslation::query()->create([
            'verse_id' => $verse->id,
            'lang' => 'ar',
            'text' => $arabicText,
        ]);

        return $verse;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string>  $arabicTranslation
     */
    private function createContext(Surah $surah, array $attributes, array $arabicTranslation): RevelationContext
    {
        /** @var RevelationContext $context */
        $context = RevelationContext::query()->create(array_merge([
            'surah_id' => $surah->id,
            'verse_from' => 1,
            'verse_to' => null,
            'period' => 'makki',
            'display_order' => 1,
            'is_active' => true,
        ], $attributes));

        $context->translations()->create(array_merge([
            'locale' => 'ar',
            'is_ready' => true,
        ], $arabicTranslation));

        return $context;
    }
}
