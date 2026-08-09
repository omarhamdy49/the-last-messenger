<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Hadith\Enums\Grade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArticleModuleLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_editor_can_fetch_surahs_and_arabic_verses_with_admin_session(): void
    {
        $this->actingAs($this->createAdminUser());

        $surah = Surah::query()->create([
            'revelation_place' => 'makkah',
            'revelation_order' => 5,
            'name_ar' => 'الفاتحة',
            'name_en' => 'Al-Fatihah',
            'verses_count' => 1,
        ]);
        $verse = Verse::query()->create([
            'surah_id' => $surah->id,
            'verse_number' => 1,
            'verse_key' => '1:1',
            'juz_number' => 1,
            'hizb_number' => 1,
            'rub_el_hizb_number' => 1,
            'ruku_number' => 1,
            'page_number' => 1,
        ]);
        VerseTranslation::query()->create([
            'verse_id' => $verse->id,
            'lang' => 'ar',
            'text' => 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ',
        ]);

        $this->getJson(route('admin.articles.modules.surahs'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $surah->id)
            ->assertJsonPath('data.0.name_ar', 'الفاتحة');

        $this->getJson(route('admin.articles.modules.verses', $surah))
            ->assertOk()
            ->assertJsonPath('data.0.verse_key', '1:1')
            ->assertJsonPath('data.0.text', 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ');
    }

    public function test_article_editor_can_filter_and_fetch_hadiths_with_admin_session(): void
    {
        $this->actingAs($this->createAdminUser());

        $collection = HadithCollection::factory()->create([
            'title_ar' => 'صحيح الاختبار',
            'title_en' => 'Test Sahih',
            'is_published' => true,
        ]);
        $hadith = Hadith::factory()->create([
            'collection_id' => $collection->id,
            'number' => '42',
            'reference_key' => '1-42',
            'text_ar' => 'إنما الأعمال بالنيات',
            'search_ar' => 'إنما الأعمال بالنيات',
            'grade' => Grade::Sahih,
            'is_published' => true,
        ]);

        $this->getJson(route('admin.articles.modules.hadith-collections'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $collection->id)
            ->assertJsonPath('data.0.name_ar', 'صحيح الاختبار');

        $this->getJson(route('admin.articles.modules.hadiths', [
            'collection_id' => $collection->id,
            'q' => 'النيات',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $hadith->id)
            ->assertJsonPath('data.0.text_ar', 'إنما الأعمال بالنيات')
            ->assertJsonPath('data.0.collection', 'صحيح الاختبار')
            ->assertJsonPath('data.0.number', '42')
            ->assertJsonPath('data.0.grade', 'sahih');
    }
}
