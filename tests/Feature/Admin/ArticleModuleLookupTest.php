<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Hadith\Enums\Grade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArticleModuleLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_editor_can_fetch_surahs_and_arabic_verses_with_admin_session(): void
    {
        $this->actingAs($this->createAdminUser());

        $chapter = Chapter::factory()->number(1)->withVerses(1)->create(['name_arabic' => 'الفاتحة', 'name_simple' => 'Al-Fatihah', 'revelation_order' => 5]);
        $verse = Verse::query()->where('verse_key', '1:1')->firstOrFail();
        Translation::factory()->create([
            'resource_id' => QuranResource::factory()->translation('ar')->create()->id,
            'verse_id' => $verse->id,
            'text' => 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ',
        ]);
        Translation::factory()->create([
            'resource_id' => QuranResource::factory()->translation('en')->create()->id,
            'verse_id' => $verse->id,
            'text' => 'In the name of Allah, the Most Compassionate, the Most Merciful.',
        ]);

        $this->getJson(route('admin.articles.modules.surahs'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $chapter->id)
            ->assertJsonPath('data.0.name_ar', 'الفاتحة');

        $this->getJson(route('admin.articles.modules.verses', ['surah' => $chapter->id, 'locale' => 'ar']))
            ->assertOk()
            ->assertJsonPath('data.0.verse_key', '1:1')
            ->assertJsonPath('data.0.text', 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ')
            ->assertJsonPath('data.0.text_locale', 'ar');

        $this->getJson(route('admin.articles.modules.verses', ['surah' => $chapter->id, 'locale' => 'en']))
            ->assertOk()
            ->assertJsonPath('data.0.text', 'In the name of Allah, the Most Compassionate, the Most Merciful.')
            ->assertJsonPath('data.0.text_locale', 'en');
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
            'text_en' => 'Actions are judged by intentions.',
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
            ->assertJsonPath('data.0.text_en', 'Actions are judged by intentions.')
            ->assertJsonPath('data.0.collection', 'صحيح الاختبار')
            ->assertJsonPath('data.0.collection_en', 'Test Sahih')
            ->assertJsonPath('data.0.number', '42')
            ->assertJsonPath('data.0.grade', 'sahih');
    }

    public function test_sahabi_browser_returns_the_requested_content_locale(): void
    {
        $this->actingAs($this->createAdminUser());

        $role = IslamicRole::factory()->sahabi()->create();
        $person = Muslim::factory()->create([
            'slug' => 'umar-ibn-al-khattab',
            'name_ar' => 'عمر بن الخطاب',
            'name_en' => 'Umar ibn al-Khattab',
            'kunya' => ['ar' => 'أبو حفص', 'en' => 'Abu Hafs'],
        ]);
        $person->roles()->attach($role);

        $this->getJson(route('admin.sahaba.browse', ['role' => 'sahabi', 'locale' => 'en']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Umar ibn al-Khattab')
            ->assertJsonPath('data.0.name_en', 'Umar ibn al-Khattab')
            ->assertJsonPath('data.0.kunya', 'Abu Hafs')
            ->assertJsonPath('data.0.kunya_en', 'Abu Hafs');

        $this->getJson(route('admin.sahaba.browse', ['role' => 'sahabi', 'locale' => 'ar']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'عمر بن الخطاب')
            ->assertJsonPath('data.0.kunya', 'أبو حفص');
    }
}
