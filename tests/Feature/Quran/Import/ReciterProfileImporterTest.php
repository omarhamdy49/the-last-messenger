<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\Reciters\ReciterProfileImporter;
use App\Domain\Quran\Models\QuranResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Reciter portraits and missing Arabic names. Quran Foundation ships neither, so they
 * are filled from the public quran.com reciter list.
 */
final class ReciterProfileImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Http::fake([
            'api.qurancdn.com/*' => Http::response(['reciters' => [
                [
                    'id' => 2,
                    'name' => 'AbdulBaset AbdulSamad',
                    'profile_picture' => 'images/reciters/1/abdelbasset-profile.jpeg',
                    'translated_name' => ['name' => 'عبد الباسط عبد الصمد'],
                ],
                [
                    'id' => 97,
                    'name' => 'Yasser Ad Dussary',
                    'profile_picture' => '',
                    'translated_name' => ['name' => 'ياسر الدوسري'],
                ],
            ]]),
            'static.qurancdn.com/*' => Http::response("\xff\xd8\xff-fake-jpeg"),
        ]);
    }

    private function reciter(string $slug, string $name): QuranResource
    {
        return QuranResource::factory()->create([
            'kind' => ResourceKind::CHAPTER_RECITER,
            'slug' => $slug,
            'name' => $name,
            'is_active' => true,
            'image' => null,
        ]);
    }

    public function test_it_downloads_a_portrait_matching_a_differently_transliterated_name(): void
    {
        // "Abdul Basit Abdul Samad" vs the source's "AbdulBaset AbdulSamad".
        $reciter = $this->reciter('chapter_reciter-2', 'Abdul Basit Abdul Samad');

        $report = app(ReciterProfileImporter::class)->import(download: true, force: false);

        $this->assertSame([], $report->failed);
        $reciter->refresh();
        $this->assertSame('quran/reciters/'.$reciter->id.'.jpeg', $reciter->image);
        Storage::disk('public')->assertExists($reciter->image);
        $this->assertSame('عبد الباسط عبد الصمد', $reciter->getTranslation('name', 'ar', false));
    }

    public function test_without_download_it_stores_the_remote_url(): void
    {
        $reciter = $this->reciter('chapter_reciter-2', 'Abdul Basit Abdul Samad');

        app(ReciterProfileImporter::class)->import(download: false, force: false);

        $this->assertSame(
            'https://static.qurancdn.com/images/reciters/1/abdelbasset-profile.jpeg',
            $reciter->refresh()->image,
        );
    }

    public function test_a_trailing_qualifier_still_matches_the_same_person(): void
    {
        $reciter = $this->reciter('chapter_reciter-173', 'AbdulBaset AbdulSamad Streaming');

        app(ReciterProfileImporter::class)->import(download: true, force: false);

        $this->assertNotNull($reciter->refresh()->image);
    }

    public function test_an_uploaded_image_survives_unless_the_import_is_forced(): void
    {
        $reciter = $this->reciter('chapter_reciter-2', 'Abdul Basit Abdul Samad');
        $reciter->update(['image' => 'uploads/by-hand.png']);

        app(ReciterProfileImporter::class)->import(download: true, force: false);
        $this->assertSame('uploads/by-hand.png', $reciter->refresh()->image);

        app(ReciterProfileImporter::class)->import(download: true, force: true);
        $this->assertSame('quran/reciters/'.$reciter->id.'.jpeg', $reciter->refresh()->image);
    }

    public function test_a_reciter_the_source_has_no_portrait_for_is_reported_not_failed(): void
    {
        $unknown = $this->reciter('chapter_reciter-160', 'Bandar Baleela');
        $pictureless = $this->reciter('chapter_reciter-174', 'Yasser ad-Dussary');

        $report = app(ReciterProfileImporter::class)->import(download: true, force: false);

        $this->assertSame([], $report->failed);
        $this->assertTrue($report->passed());
        $this->assertNull($unknown->refresh()->image);
        $this->assertNull($pictureless->refresh()->image);
        // The name still arrives even when the portrait does not.
        $this->assertSame('ياسر الدوسري', $pictureless->getTranslation('name', 'ar', false));
    }
}
