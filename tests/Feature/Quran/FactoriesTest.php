<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Enums\CharType;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\AudioFile;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\ChapterInfo;
use App\Domain\Quran\Models\Language;
use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Tafsir;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exercises the new schema end to end: every factory persists against the real
 * migrations (FKs, uniques, non-incrementing keys).
 */
final class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_with_verses_builds_consistent_keys(): void
    {
        $chapter = Chapter::factory()->number(1)->withVerses(7)->create();

        $this->assertSame(1, $chapter->id);
        $this->assertSame(7, $chapter->fresh()->verses_count);
        $this->assertSame(
            ['1:1', '1:2', '1:3', '1:4', '1:5', '1:6', '1:7'],
            Verse::query()->orderBy('id')->pluck('verse_key')->all(),
        );
        $this->assertSame('بسم الله الرحمن الرحيم', Verse::query()->where('verse_key', '1:1')->value('text_uthmani_simple'));
    }

    public function test_default_chapter_factory_uses_the_next_free_number(): void
    {
        Chapter::factory()->number(1)->create();
        $second = Chapter::factory()->create();

        $this->assertSame(2, $second->id);
    }

    public function test_every_content_factory_persists(): void
    {
        $chapter = Chapter::factory()->number(1)->withVerses(2)->create();
        $verse = Verse::query()->where('verse_key', '1:1')->firstOrFail();

        $word = Word::factory()->for($verse)->create();
        Word::factory()->for($verse)->end()->create();
        $translation = Translation::factory()->for($verse)->withFootnote()->create();
        $tafsir = Tafsir::factory()->for($verse)->create();
        $info = ChapterInfo::factory()->for($chapter)->create();
        $audio = AudioFile::factory()->for($verse)->create();
        $chapterAudio = ChapterAudioFile::factory()->for($chapter)->withTimestamps()->create();
        $mushaf = Mushaf::factory()->asDefault()->create();
        $language = Language::factory()->iso('ar', 'Arabic', 'rtl')->create();

        $this->assertSame('1:1:1', $word->location);
        $this->assertSame(CharType::END, Word::query()->where('verse_id', $verse->id)->orderByDesc('position')->first()?->char_type);
        $this->assertSame(['1' => 'A footnote.'], $translation->fresh()->foot_notes);
        $this->assertSame(ResourceKind::TRANSLATION, $translation->resource->kind);
        $this->assertSame(ResourceKind::TAFSIR, $tafsir->resource->kind);
        $this->assertSame(ResourceKind::CHAPTER_INFO, $info->resource->kind);
        $this->assertSame('https://verses.quran.foundation/Alafasy/mp3/001001.mp3', $audio->url);
        $this->assertTrue($chapterAudio->fresh()->has_timestamps);
        $this->assertCount(2, $chapterAudio->verseTimestamps);
        $this->assertTrue($mushaf->is_default);
        $this->assertTrue($language->isRtl());
    }

    public function test_resource_translations_use_the_localized_content_trait(): void
    {
        $resource = QuranResource::factory()->chapterReciter()->create(['name' => 'Mishari Rashid al-Afasy']);

        $resource->syncTranslations(['name' => ['ar' => 'مشاري راشد العفاسي', 'en' => 'Mishari Rashid al-Afasy']]);

        $this->assertSame('مشاري راشد العفاسي', $resource->fresh()->displayName('ar'));
        $this->assertSame('Mishari Rashid al-Afasy', $resource->fresh()->displayName('fr'));
    }
}
