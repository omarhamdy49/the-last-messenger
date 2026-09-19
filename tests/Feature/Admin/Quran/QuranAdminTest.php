<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Quran;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\ChapterInfo;
use App\Domain\Quran\Models\Language;
use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\SyncRun;
use App\Domain\Quran\Models\Tafsir;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use App\Domain\Quran\Services\PublicReciterService;
use App\Jobs\RunArtisanCommandJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Quran\Import\Concerns\FakesQuranFoundation;
use Tests\TestCase;

/**
 * Admin CRUD for the Quran Foundation–backed module (006, US3).
 */
final class QuranAdminTest extends TestCase
{
    use FakesQuranFoundation;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
        $this->actingAs($this->admin, 'admin');
        Chapter::factory()->number(1)->withVerses(7)->create();
    }

    private function actAsEditor(): void
    {
        $this->actingAs($this->createAdminUser(role: 'editor'), 'admin');
    }

    // --- Chapters -------------------------------------------------------------

    public function test_chapter_index_show_and_edit_render(): void
    {
        $this->get(route('admin.quran.chapters.index'))->assertOk()->assertSee('الفاتحة', false);
        $this->get(route('admin.quran.chapters.show', 1))->assertOk();
        $this->get(route('admin.quran.chapters.edit', 1))->assertOk();
        $this->getJson(route('admin.quran.chapters.search', ['q' => 'Fatihah']))->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_chapter_update_saves_localized_names(): void
    {
        $this->put(route('admin.quran.chapters.update', 1), [
            'name' => ['ar' => 'الفاتحة', 'en' => 'The Opener', 'fr' => 'L\'Ouverture'],
            'is_published' => '1',
        ])->assertRedirect(route('admin.quran.chapters.show', 1));

        $this->assertSame('The Opener', Chapter::query()->find(1)->getTranslation('name', 'en'));
    }

    public function test_editor_can_view_but_not_edit_chapters(): void
    {
        $this->actAsEditor();

        $this->get(route('admin.quran.chapters.index'))->assertOk();
        $this->put(route('admin.quran.chapters.update', 1), ['name' => ['ar' => 'x']])->assertForbidden();
    }

    // --- Verses and words -----------------------------------------------------

    public function test_verse_pages_render(): void
    {
        $verse = Verse::query()->first();
        Word::factory()->create(['verse_id' => $verse->id]);

        $this->get(route('admin.quran.verses.index', ['chapter' => 1]))->assertOk()->assertSee('1:1');
        $this->get(route('admin.quran.verses.show', $verse->id))->assertOk();
        $this->get(route('admin.quran.verses.edit', $verse->id))->assertOk();
        $this->get(route('admin.quran.verses.words.index', $verse->id))->assertOk();
    }

    public function test_editing_verse_text_locks_it_and_sanitizes_tajweed(): void
    {
        $verse = Verse::query()->first();

        $this->put(route('admin.quran.verses.update', $verse->id), [
            'text_uthmani' => 'بِسْمِ ٱللَّهِ <b>x</b>',
            'text_uthmani_tajweed' => '<tajweed class="ham_wasl" onclick="alert(1)">ٱ</tajweed><script>x</script>',
        ])->assertRedirect();

        $verse->refresh();
        $this->assertTrue($verse->is_locked);
        $this->assertSame('بِسْمِ ٱللَّهِ x', $verse->text_uthmani);
        $this->assertSame('<tajweed class="ham_wasl">ٱ</tajweed>x', $verse->text_uthmani_tajweed);
    }

    public function test_bulk_lock_and_single_lock_toggle(): void
    {
        $ids = Verse::query()->limit(3)->pluck('id')->all();

        $this->post(route('admin.quran.verses.bulk-lock'), ['ids' => $ids, 'locked' => '1'])->assertRedirect();
        $this->assertSame(3, Verse::query()->where('is_locked', true)->count());

        $this->patch(route('admin.quran.verses.lock', $ids[0]))->assertRedirect();
        $this->assertFalse(Verse::query()->find($ids[0])->is_locked);
    }

    public function test_corpus_has_no_create_or_delete(): void
    {
        $this->get('/su/portal/v1/admin/quran/verses/create')->assertNotFound();
        $this->delete('/su/portal/v1/admin/quran/verses/1')->assertStatus(405);
        $this->delete('/su/portal/v1/admin/quran/chapters/1')->assertStatus(405);
    }

    public function test_word_glosses_are_upserted_and_cleared(): void
    {
        $word = Word::factory()->create(['verse_id' => 1]);
        $gloss = QuranResource::factory()->wordTranslation('en')->create();

        $this->put(route('admin.quran.words.update', $word->id), ['glosses' => [$gloss->id => 'In (the) name']])->assertRedirect();
        $this->assertSame('In (the) name', DB::table('quran_word_glosses')->where('word_id', $word->id)->value('text'));

        $this->put(route('admin.quran.words.update', $word->id), ['glosses' => [$gloss->id => '']])->assertRedirect();
        $this->assertSame(0, DB::table('quran_word_glosses')->where('word_id', $word->id)->count());
    }

    // --- Sections, mushafs, languages ----------------------------------------

    public function test_sections_mushafs_and_languages(): void
    {
        DB::table('quran_sections')->insert(['type' => 'juz', 'number' => 1, 'first_verse_id' => 1, 'last_verse_id' => 7, 'verses_count' => 7, 'verse_mapping' => json_encode(['1' => '1-7'])]);
        $mushaf = Mushaf::factory()->create(['name' => 'QCF V2']);

        $this->get(route('admin.quran.sections.index', 'juz'))->assertOk();
        $this->get(route('admin.quran.sections.show', ['juz', 1]))->assertOk();
        $this->get(route('admin.quran.mushafs.index'))->assertOk()->assertSee('QCF V2');
        $this->get(route('admin.quran.mushafs.show', $mushaf->id))->assertOk();

        $this->put(route('admin.quran.mushafs.update', $mushaf->id), ['name' => 'Madani', 'is_active' => '1', 'is_default' => '1'])->assertRedirect();
        $this->assertTrue($mushaf->fresh()->is_default);

        $this->post(route('admin.quran.languages.store'), ['name' => 'Hausa', 'iso_code' => 'ha', 'direction' => 'ltr', 'is_active' => '1'])
            ->assertRedirect(route('admin.quran.languages.index'));
        $this->assertGreaterThanOrEqual(10_000, Language::query()->where('iso_code', 'ha')->value('id'));

        $this->post(route('admin.quran.languages.store'), ['name' => 'Dup', 'iso_code' => 'ha', 'direction' => 'ltr'])->assertSessionHasErrors('iso_code');
        $this->get(route('admin.quran.languages.index'))->assertOk()->assertSee('Hausa');
    }

    // --- Resources -------------------------------------------------------------

    public function test_manual_resource_store_duplicate_slug_and_show(): void
    {
        $payload = ['name' => 'My translation', 'slug' => 'my-translation', 'language_iso' => null, 'is_active' => '1', 'display_order' => 3];

        $this->post(route('admin.quran.resources.store', 'translations'), $payload)->assertRedirect();
        $resource = QuranResource::query()->where('slug', 'my-translation')->firstOrFail();
        $this->assertSame('manual', $resource->source->value);

        $this->post(route('admin.quran.resources.store', 'translations'), $payload)->assertSessionHasErrors('slug');
        // The same slug is fine for another kind.
        $this->post(route('admin.quran.resources.store', 'tafsirs'), $payload)->assertRedirect();

        $this->get(route('admin.quran.resources.index', 'translations'))->assertOk()->assertSee('My translation');

        // Alphabetical by default, hidden rows filterable, other sorts accepted.
        $zed = QuranResource::factory()->translation()->manual()->create(['name' => 'Zed', 'display_order' => 0]);
        $abe = QuranResource::factory()->translation()->manual()->create(['name' => 'Abe', 'display_order' => 9]);
        $this->get(route('admin.quran.resources.index', 'translations'))->assertSeeInOrder(['Abe', 'Zed']);
        $this->get(route('admin.quran.resources.index', ['translations', 'sort' => 'name_desc']))->assertSeeInOrder(['Zed', 'Abe']);
        $this->post(route('admin.quran.resources.hide', $zed->id));
        $this->get(route('admin.quran.resources.index', ['translations', 'status' => 'hidden']))->assertSee('Zed')->assertDontSee('Abe');
        $this->get(route('admin.quran.resources.show', $resource->id))->assertOk();
        $this->get(route('admin.quran.resources.edit', $resource->id))->assertOk();
    }

    public function test_resource_image_can_be_an_external_url_and_the_wizard_renders(): void
    {
        $resource = QuranResource::factory()->translation()->manual()->create();
        $payload = ['name' => 'Img', 'slug' => 'img', 'is_active' => '1', 'image_id' => '', 'remove_image' => '0'];

        $this->put(route('admin.quran.resources.update', $resource->id), $payload + ['image_url' => 'https://cdn.example.com/r.webp'])->assertRedirect();
        $this->assertSame('https://cdn.example.com/r.webp', $resource->refresh()->image);

        // An untouched form re-posts the same URL; a bad scheme is refused.
        $this->put(route('admin.quran.resources.update', $resource->id), $payload + ['image_url' => 'javascript:alert(1)'])->assertSessionHasErrors('image_url');
        $this->put(route('admin.quran.resources.update', $resource->id), ['remove_image' => '1', 'image_url' => ''] + $payload)->assertRedirect();
        $this->assertNull($resource->refresh()->image);

        $this->get(route('admin.quran.resources.edit', $resource->id))->assertOk()->assertSee('name="image_url"', false)->assertSee('data-wysiwyg', false);

        // A replaced or removed local portrait leaves no file; prune sweeps what nothing references.
        Storage::fake('public');
        Storage::disk('public')->put('quran/reciters/old.jpeg', 'x');
        Storage::disk('public')->put('quran/reciters/orphan.jpeg', 'x');
        $resource->forceFill(['image' => 'quran/reciters/old.jpeg'])->save();
        $this->put(route('admin.quran.resources.update', $resource->id), $payload + ['image_url' => 'https://cdn.example.com/new.webp'])->assertRedirect();
        Storage::disk('public')->assertMissing('quran/reciters/old.jpeg');
        Storage::disk('public')->assertExists('quran/reciters/orphan.jpeg');
        $this->artisan('quran:reciter:images', ['--prune' => true])->assertSuccessful();
        Storage::disk('public')->assertMissing('quran/reciters/orphan.jpeg');
        $this->get(route('admin.quran.resources.create', 'chapter_reciters'))->assertOk()->assertSee('data-wizard-step="source"', false);
    }

    public function test_resource_slug_is_generated_when_blank_and_always_slugified(): void
    {
        $store = fn (array $payload) => $this->postJson(route('admin.quran.resources.store', 'chapter_reciters'), $payload + ['is_active' => '1']);

        // Blank: English name wins, then the base name, then the Arabic one; collisions get a suffix.
        $store(['name' => 'قارئ', 'slug' => '', 'name_translations' => ['en' => 'Abdul Basit', 'ar' => 'عبد الباسط']])->assertCreated()->assertJsonPath('data.slug', 'abdul-basit');
        $store(['name' => 'Abdul Basit', 'slug' => ''])->assertCreated()->assertJsonPath('data.slug', 'abdul-basit-1');
        $store(['name' => '!!!', 'name_translations' => ['ar' => 'عبد الباسط']])->assertCreated()->assertJsonPath('data.slug', 'aabd-albast');
        $store(['name' => '!!!', 'slug' => ''])->assertUnprocessable()->assertJsonValidationErrors('slug');

        // Typed text is run through Str::slug(); a typed duplicate is still an error, not a silent suffix.
        $store(['name' => 'X', 'slug' => ' My Réciter_Slug! '])->assertCreated()->assertJsonPath('data.slug', 'my-reciter-slug');
        $store(['name' => 'Y', 'slug' => 'My Reciter Slug'])->assertUnprocessable()->assertJsonValidationErrors('slug');

        // An untouched stored slug is kept verbatim (imported slugs carry underscores); clearing it regenerates.
        $imported = QuranResource::factory()->create(['kind' => 'chapter_reciter', 'slug' => 'chapter_reciter-9', 'name' => 'Imported One']);
        $update = fn (array $payload) => $this->putJson(route('admin.quran.resources.update', $imported->id), $payload + ['name' => 'Imported One', 'is_active' => '1']);
        $update(['slug' => 'chapter_reciter-9'])->assertOk()->assertJsonPath('data.slug', 'chapter_reciter-9');
        $update(['slug' => ''])->assertOk()->assertJsonPath('data.slug', 'imported-one');
    }

    public function test_reciter_fields_are_rejected_for_non_reciter_kinds(): void
    {
        $this->post(route('admin.quran.resources.store', 'translations'), ['name' => 'X', 'slug' => 'x', 'qirat' => 'Hafs'])
            ->assertSessionHasErrors('unexpected_fields');

        $this->post(route('admin.quran.resources.store', 'chapter_reciters'), [
            'name' => 'Reader', 'slug' => 'reader', 'qirat' => 'Hafs', 'style' => 'murattal', 'external_source' => 'mp3quran', 'external_ref_id' => '123',
        ])->assertRedirect();
        $this->assertSame('123', QuranResource::query()->where('slug', 'reader')->value('external_ref_id'));
    }

    public function test_toggle_reorder_and_destroy(): void
    {
        $qf = QuranResource::factory()->translation()->qf(20)->create(['is_active' => true]);
        $manual = QuranResource::factory()->translation()->manual()->create();

        $this->post(route('admin.quran.resources.toggle', [$qf->id, 'sync_enabled']))->assertRedirect();
        $this->assertTrue($qf->fresh()->sync_enabled);
        $this->post(route('admin.quran.resources.toggle', [$manual->id, 'sync_enabled']))->assertSessionHas('error');

        $this->postJson(route('admin.quran.resources.reorder', 'translations'), ['ids' => [$manual->id, $qf->id]])->assertOk();
        $this->assertSame(1, $qf->fresh()->display_order);

        // Hide keeps the row; activating again unhides it.
        $this->post(route('admin.quran.resources.hide', $qf->id))->assertRedirect();
        $this->assertFalse($qf->fresh()->is_active);
        $this->assertTrue($qf->fresh()->metadata['hidden_by_admin']);
        $this->post(route('admin.quran.resources.toggle', [$qf->id, 'is_active']))->assertRedirect();
        $this->assertTrue($qf->fresh()->is_active);
        $this->assertArrayNotHasKey('hidden_by_admin', $qf->fresh()->metadata ?? []);

        // Delete is permanent for QF and manual rows alike, and takes a reciter's recordings and files.
        Storage::fake('public');
        $reciter = QuranResource::factory()->chapterReciter()->qf(12)->create();
        $recording = QuranResource::factory()->chapterReciter()->manual()->create(['parent_id' => $reciter->id]);
        Storage::disk('public')->put("quran/audio/{$reciter->id}/001.mp3", 'x');
        Storage::disk('public')->put("quran/audio/{$recording->id}/001.mp3", 'x');

        $this->delete(route('admin.quran.resources.destroy', $reciter->id))->assertRedirect();
        $this->assertNull(QuranResource::query()->find($reciter->id));
        $this->assertNull(QuranResource::query()->find($recording->id));
        Storage::disk('public')->assertMissing("quran/audio/{$reciter->id}/001.mp3");
        Storage::disk('public')->assertMissing("quran/audio/{$recording->id}/001.mp3");
        $this->delete(route('admin.quran.resources.destroy', $manual->id))->assertRedirect();
        $this->assertNull(QuranResource::query()->find($manual->id));
    }

    public function test_import_from_qf_creates_the_catalogue_row(): void
    {
        $this->setUpQuranFoundation();
        $en = $this->qfFixture('catalogue-translation-en');
        $ar = $this->qfFixture('catalogue-translation-ar');
        Http::fake(function ($request) use ($en, $ar) {
            $url = urldecode($request->url());
            if (str_contains($url, '/info')) {
                return Http::response(['message' => 'not found', 'type' => 'not_found', 'success' => false], 404);
            }
            if (str_contains($url, 'resources/translations')) {
                return Http::response(str_contains($url, 'language=ar') ? $ar : $en);
            }

            return Http::response(['message' => 'unexpected '.$url], 500);
        });
        $qfId = (int) $en['translations'][0]['id'];

        $this->post(route('admin.quran.resources.import-from-qf', 'translations'), ['qf_id' => $qfId])->assertRedirect();

        $resource = QuranResource::query()->where('kind', 'translation')->where('qf_id', $qfId)->first();
        $this->assertNotNull($resource);
        $this->assertFalse($resource->is_active, 'importing never activates');
    }

    public function test_resource_sync_queues_the_right_command(): void
    {
        Bus::fake();
        $qf = QuranResource::factory()->translation()->qf(20)->create();

        $this->post(route('admin.quran.resources.sync', $qf->id))->assertRedirect();

        Bus::assertDispatched(RunArtisanCommandJob::class);
        $this->assertSame('quran:qf:sync', DB::table('command_runs')->latest('id')->value('command'));
        $this->assertStringContainsString('translations:20', (string) DB::table('command_runs')->latest('id')->value('input'));
    }

    // --- Resource rows --------------------------------------------------------

    public function test_translation_rows_create_update_lock_import_and_validate(): void
    {
        $resource = QuranResource::factory()->translation()->manual()->create();

        $this->post(route('admin.quran.resources.translations.store', $resource->id), ['verse_key' => '1:1', 'text' => 'In the name <sup foot_note="9" onclick="x">1</sup>', 'is_locked' => '1'])
            ->assertRedirect();
        $row = Translation::query()->where('resource_id', $resource->id)->firstOrFail();
        $this->assertSame('In the name <sup foot_note="9">1</sup>', $row->text);
        $this->assertTrue($row->is_locked);

        $this->put(route('admin.quran.resources.translations.update', [$resource->id, $row->id]), ['text' => 'Updated', 'is_locked' => '0'])->assertRedirect();
        $this->assertSame('Updated', $row->fresh()->text);
        $this->assertFalse($row->fresh()->is_locked);

        $file = UploadedFile::fake()->createWithContent('rows.json', json_encode([['verse_key' => '1:2', 'text' => 'Praise'], ['verse_key' => '1:3', 'text' => 'Mercy']]));
        $this->post(route('admin.quran.resources.translations.import', $resource->id), ['file' => $file])->assertSessionHas('success');
        $this->assertSame(3, Translation::query()->where('resource_id', $resource->id)->count());

        $this->post(route('admin.quran.resources.translations.store', $resource->id), ['verse_key' => '99:1', 'text' => 'x'])->assertSessionHasErrors('verse_key');
        $this->get(route('admin.quran.resources.translations.index', [$resource->id, 'chapter' => 1]))->assertOk()->assertSee('Praise');
    }

    public function test_tafsir_and_chapter_info_rows(): void
    {
        $tafsir = QuranResource::factory()->tafsir()->manual()->create();
        $info = QuranResource::factory()->chapterInfo()->manual()->create();

        $this->post(route('admin.quran.resources.tafsirs.store', $tafsir->id), ['verse_key' => '1:1', 'text' => '<p>Tafsir</p><script>x</script>'])->assertRedirect();
        $this->assertStringNotContainsString('<script', (string) Tafsir::query()->where('resource_id', $tafsir->id)->value('text'));
        $this->get(route('admin.quran.resources.tafsirs.index', $tafsir->id))->assertOk();

        $this->post(route('admin.quran.resources.chapter-infos.store', $info->id), ['chapter_id' => 1, 'text' => '<p>About</p>', 'short_text' => 'Short'])->assertRedirect();
        $row = ChapterInfo::query()->where('resource_id', $info->id)->firstOrFail();
        $this->put(route('admin.quran.resources.chapter-infos.update', [$info->id, $row->id]), ['text' => '<p>New</p>', 'chapter_id' => 2])
            ->assertSessionHasErrors('chapter_id');
        $this->delete(route('admin.quran.resources.chapter-infos.destroy', [$info->id, $row->id]))->assertRedirect();
        $this->assertSame(0, ChapterInfo::query()->where('resource_id', $info->id)->count());

        // A tafsir id on the translations URL is a 404, not a cross-kind write.
        $this->get(route('admin.quran.resources.translations.index', $tafsir->id))->assertNotFound();
    }

    // --- Audio ----------------------------------------------------------------

    public function test_ayah_audio_url_upload_and_template_fill(): void
    {
        Storage::fake('public');
        $recitation = QuranResource::factory()->recitation()->manual()->create();

        $this->post(route('admin.quran.resources.audio-files.store', $recitation->id), ['verse_key' => '1:1', 'url' => 'https://cdn.test/001001.mp3'])->assertRedirect();
        $this->assertSame('https://cdn.test/001001.mp3', DB::table('quran_audio_files')->where('verse_id', 1)->value('url'));

        $upload = UploadedFile::fake()->create('a.mp3', 100, 'audio/mpeg');
        $this->post(route('admin.quran.resources.audio-files.store', $recitation->id), ['verse_key' => '1:2', 'file' => $upload])->assertRedirect();
        Storage::disk('public')->assertExists("quran/audio/{$recitation->id}/1_2.mp3");
        $this->assertTrue((bool) DB::table('quran_audio_files')->where('verse_id', 2)->value('is_local'));

        $this->post(route('admin.quran.resources.audio-files.fill', $recitation->id), ['url_template' => 'https://cdn.test/{surah:03}{ayah:03}.mp3', 'chapter' => 1])->assertRedirect();
        $this->assertSame(7, DB::table('quran_audio_files')->where('resource_id', $recitation->id)->count());

        $this->get(route('admin.quran.resources.audio-files.index', [$recitation->id, 'chapter' => 1]))->assertOk();
    }

    public function test_chapter_audio_and_timestamps_require_every_verse(): void
    {
        $reciter = QuranResource::factory()->chapterReciter()->manual()->create();
        $this->post(route('admin.quran.resources.chapter-audio.store', $reciter->id), ['chapter_id' => 1, 'url' => 'https://cdn.test/001.mp3'])->assertRedirect();
        $file = ChapterAudioFile::query()->where('resource_id', $reciter->id)->firstOrFail();

        $partial = json_encode([['verse_key' => '1:1', 'from' => 0, 'to' => 1000]]);
        $this->put(route('admin.quran.resources.chapter-audio.timestamps.update', [$reciter->id, $file->id]), ['timestamps' => $partial])
            ->assertSessionHasErrors('timestamps');

        $full = json_encode(array_map(static fn (int $n): array => ['verse_key' => '1:'.$n, 'from' => ($n - 1) * 1000, 'to' => $n * 1000], range(1, 7)));
        $this->put(route('admin.quran.resources.chapter-audio.timestamps.update', [$reciter->id, $file->id]), ['timestamps' => $full])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue($file->fresh()->has_timestamps);

        $this->get(route('admin.quran.resources.chapter-audio.index', $reciter->id))->assertOk();
        $this->get(route('admin.quran.resources.chapter-audio.timestamps.edit', [$reciter->id, $file->id]))->assertOk()->assertSee('1:7');
    }

    // --- Sync -----------------------------------------------------------------

    public function test_sync_dashboard_and_actions(): void
    {
        Bus::fake();
        $run = SyncRun::query()->create(['mode' => 'incremental', 'started_at' => now(), 'status' => 'succeeded', 'errors' => [['key' => 'translations:20', 'reason' => 'boom']]]);

        $this->get(route('admin.quran.sync.index'))->assertOk()->assertSee((string) $run->id);
        $this->get(route('admin.quran.sync.runs.show', $run->id))->assertOk()->assertSee('boom');

        $this->post(route('admin.quran.sync.bootstrap'), ['resources' => 'translations:20'])->assertRedirect();
        $this->post(route('admin.quran.sync.incremental'))->assertRedirect();
        $this->post(route('admin.quran.sync.assets'), ['images' => '1', 'fonts' => ['v2']])->assertRedirect();
        Bus::assertDispatchedTimes(RunArtisanCommandJob::class, 3);

        $this->post(route('admin.quran.sync.bootstrap'), ['resources' => 'bad filter'])->assertSessionHasErrors('resources');
    }

    public function test_sync_actions_need_the_sync_permission(): void
    {
        $this->actAsEditor();

        $this->get(route('admin.quran.sync.index'))->assertOk();
        $this->post(route('admin.quran.sync.incremental'))->assertForbidden();
    }

    // --- Request hygiene ------------------------------------------------------

    public function test_unknown_fields_are_rejected(): void
    {
        $verse = Verse::query()->first();
        $resource = QuranResource::factory()->translation()->manual()->create();

        $this->put(route('admin.quran.chapters.update', 1), ['name' => ['ar' => 'x'], 'verses_count' => 1])->assertSessionHasErrors('unexpected_fields');
        $this->put(route('admin.quran.verses.update', $verse->id), ['text_uthmani' => 'x', 'chapter_id' => 2])->assertSessionHasErrors('unexpected_fields');
        $this->putJson(route('admin.quran.resources.update', $resource->id), ['name' => 'x', 'slug' => 'x', 'qf_id' => 5, 'kind' => 'tafsir'])
            ->assertUnprocessable()->assertJsonValidationErrors('unexpected_fields');
    }

    public function test_a_reciter_can_be_filed_under_another_as_a_recording_and_released(): void
    {
        $main = QuranResource::factory()->chapterReciter()->create(['name' => 'Abdul Basit', 'slug' => 'abdul-basit', 'is_active' => true]);
        $mujawwad = QuranResource::factory()->chapterReciter()->create(['name' => 'Abdul Basit Mujawwad', 'slug' => 'abdul-basit-mujawwad', 'is_active' => true]);
        $nested = QuranResource::factory()->chapterReciter()->create(['name' => 'Abdul Basit Warsh', 'slug' => 'abdul-basit-warsh', 'is_active' => true, 'parent_id' => $mujawwad->id]);
        $ayah = QuranResource::factory()->recitation()->create();

        $this->post(route('admin.quran.resources.merge', $mujawwad->id), ['into' => $main->id])
            ->assertRedirect(route('admin.quran.resources.show', $main->id));

        // Ids are untouched; what was filed under the child follows it, so the tree stays one level deep.
        $this->assertSame($main->id, $mujawwad->fresh()->parent_id);
        $this->assertSame($main->id, $nested->fresh()->parent_id);

        $this->post(route('admin.quran.resources.merge', $main->id), ['into' => $main->id])->assertSessionHas('error');
        $this->post(route('admin.quran.resources.merge', $ayah->id), ['into' => $main->id])->assertSessionHas('error');
        $this->assertNull($ayah->fresh()->parent_id);

        $this->get(route('admin.quran.resources.show', $main->id))->assertOk()->assertSee('Abdul Basit Mujawwad');
        $this->get(route('admin.quran.resources.show', $mujawwad->id))->assertOk()->assertSee(__('quran.manage.recording_of', [], 'ar'));

        // The public directory lists the reciter once; the recording is reached from their page.
        $listing = app(PublicReciterService::class)->listing('en');
        $this->assertSame(['abdul-basit'], array_column($listing['all'], 'slug'));
        $this->assertSame(3, $listing['all'][0]['recordingsCount']);
        $page = app(PublicReciterService::class)->show('abdul-basit-mujawwad', 'en');
        $this->assertSame(['abdul-basit', 'abdul-basit-mujawwad', 'abdul-basit-warsh'], array_column($page['recordings'], 'slug'));

        $this->post(route('admin.quran.resources.release', $mujawwad->id))->assertRedirect();
        $this->assertNull($mujawwad->fresh()->parent_id);

        // Grouping reciters is an edit: a role without `edit quran` cannot do it.
        $this->actAsEditor();
        $this->post(route('admin.quran.resources.merge', $mujawwad->id), ['into' => $main->id])->assertForbidden();
        $this->assertNull($mujawwad->fresh()->parent_id);
    }

    public function test_the_audio_manager_renders_and_serves_timings_as_json(): void
    {
        $reciter = QuranResource::factory()->chapterReciter()->create();
        $file = ChapterAudioFile::factory()->create(['resource_id' => $reciter->id, 'chapter_id' => 1, 'url' => 'https://audio.test/001.mp3']);

        $this->get(route('admin.quran.resources.chapter-audio.index', ['resource' => $reciter->id, 'chapter' => 1]))
            ->assertOk()->assertSee('https://audio.test/001.mp3')->assertSee('id="surah-1"', false);

        $this->getJson(route('admin.quran.resources.chapter-audio.timestamps.edit', [$reciter->id, $file->id]))
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data', []);

        // The reciter page plays the same files from its surah list.
        $this->get(route('admin.quran.resources.show', $reciter->id))->assertOk()->assertSee('quran-track:toggle', false);
    }
}
