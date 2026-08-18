<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Blog\Services\ArticleBulkHandler;
use App\Domain\Media\Services\MediaProgramBulkHandler;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Services\VerseBulkHandler;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Services\SeerahEventBulkHandler;
use App\Models\Article;
use Database\Factories\MediaProgramFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bulk-operation preview across all four handlers.
 *
 * AbstractBulkOperationHandler::preview() ran a single `->get(['id', 'title', 'name'])`
 * for every module. Checked against the production schema, no table has both columns:
 * `articles` has `title` only, and `seerah_events`, `quran_verses` and `media_programs`
 * have neither (their titles live in translation tables). So preview() raised
 * SQLSTATE[42S22] Unknown column on all four. There were no tests on it.
 */
class BulkOperationPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_preview_returns_the_localized_title(): void
    {
        $article = Article::factory()->create([
            'title' => ['ar' => 'مقال تجريبي', 'en' => 'Test article'],
        ]);

        $preview = app(ArticleBulkHandler::class)->preview([$article->id]);

        $this->assertSame(
            [['id' => $article->id, 'title' => 'مقال تجريبي']],
            $preview
        );
    }

    public function test_verse_preview_uses_the_verse_key(): void
    {
        $surah = Surah::create([
            'revelation_place' => 'makkah',
            'revelation_order' => 5,
            'bismillah_pre' => true,
            'name_ar' => 'سورة الاختبار',
            'name_en' => 'Test Surah',
            'transliteration' => 'Test-Surah',
            'verses_count' => 7,
        ]);

        $verse = Verse::create([
            'surah_id' => $surah->id,
            'verse_number' => 255,
            'verse_key' => '2:255',
            'juz_number' => 3,
            'hizb_number' => 5,
            'rub_el_hizb_number' => 20,
            'ruku_number' => 35,
            'manzil_number' => 1,
            'page_number' => 42,
        ]);

        $this->assertSame(
            [['id' => $verse->id, 'title' => '2:255']],
            app(VerseBulkHandler::class)->preview([$verse->id])
        );
    }

    public function test_seerah_event_preview_reads_the_translation_table(): void
    {
        $event = SeerahEvent::factory()->create();

        // Compare against the model's own accessor rather than a hardcoded string — the
        // factory seeds its own translations, and what matters is that preview() reads
        // the translation table at all (seerah_events has no title column).
        $expected = $event->fresh()->getTranslation('title');
        $this->assertNotEmpty($expected, 'fixture produced no translated title');

        $this->assertSame(
            [['id' => $event->id, 'title' => $expected]],
            app(SeerahEventBulkHandler::class)->preview([$event->id])
        );
    }

    public function test_media_program_preview_reads_the_translation_table(): void
    {
        // MediaProgram declares no newFactory(), so the factory can't be auto-resolved
        // from the Domain namespace — same call style as tests/Feature/PublicVisibilityGatingTest.
        $program = MediaProgramFactory::new()->create();

        $expected = $program->fresh()->getTranslation('title');
        $this->assertNotEmpty($expected, 'fixture produced no translated title');

        $this->assertSame(
            [['id' => $program->id, 'title' => $expected]],
            app(MediaProgramBulkHandler::class)->preview([$program->id])
        );
    }

    /**
     * preview() feeds a confirmation dialog, so it must stay bounded regardless of how
     * many ids the caller passes.
     */
    public function test_preview_is_capped_at_ten_rows(): void
    {
        $surah = Surah::create([
            'revelation_place' => 'makkah',
            'revelation_order' => 5,
            'bismillah_pre' => true,
            'name_ar' => 'سورة الاختبار',
            'name_en' => 'Test Surah',
            'transliteration' => 'Test-Surah',
            'verses_count' => 20,
        ]);

        $ids = [];
        for ($n = 1; $n <= 15; $n++) {
            $ids[] = Verse::create([
                'surah_id' => $surah->id,
                'verse_number' => $n,
                'verse_key' => "{$surah->id}:{$n}",
                'juz_number' => 1,
                'hizb_number' => 1,
                'rub_el_hizb_number' => 1,
                'ruku_number' => 1,
                'manzil_number' => 1,
                'page_number' => 1,
            ])->id;
        }

        $this->assertCount(10, app(VerseBulkHandler::class)->preview($ids));
    }

    /**
     * A label must never be blank — that was the practical symptom of the old
     * `$item->title ?? $item->name ?? 'N/A'` chain once the query stopped throwing.
     */
    public function test_preview_falls_back_to_the_id_when_no_translation_exists(): void
    {
        $event = SeerahEvent::factory()->create();
        $event->translations()->delete();

        $preview = app(SeerahEventBulkHandler::class)->preview([$event->id]);

        $this->assertSame([['id' => $event->id, 'title' => '#'.$event->id]], $preview);
    }
}
