<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminReciterShowTest extends TestCase
{
    use RefreshDatabase;

    private Reciter $recording;

    protected function setUp(): void
    {
        parent::setUp();

        // revelation_order deliberately differs from id so a wrong "number" is caught.
        DB::table('quran_surahs')->insert(collect(range(1, 30))->map(fn (int $i) => [
            'id' => $i, 'name_ar' => 'سورة '.$i, 'name_en' => 'Surah '.$i, 'transliteration' => $i === 2 ? 'Al-Baqarah' : 'Surah-'.$i,
            'revelation_place' => 'makkah', 'revelation_order' => 115 - $i, 'verses_count' => 7, 'pages' => json_encode([1]),
            'created_at' => now(), 'updated_at' => now(),
        ])->all());

        $this->recording = $this->makeRecording('husary-mujawwad', 'mujawwad');
        $sibling = $this->makeRecording('husary-murattal', 'murattal');
        $profile = ReciterProfile::create(['identity_key' => 'husary', 'slug' => 'husary', 'is_active' => true, 'default_recording_id' => $sibling->id]);
        $profile->translations()->create(['locale' => 'ar', 'name' => 'الحصري']);
        Reciter::whereIn('id', [$this->recording->id, $sibling->id])->update(['profile_id' => $profile->id]);
        $this->recording->refresh();

        $source = $this->recording->sources->first();
        foreach (range(1, 5) as $surah) {
            Recitation::create(['reciter_id' => $this->recording->id, 'source_id' => $source->id, 'surah_id' => $surah, 'audio_url' => $source->base_url.$surah.'.mp3', 'format' => 'mp3', 'duration_seconds' => 600]);
        }
    }

    private function makeRecording(string $slug, string $style): Reciter
    {
        $recording = Reciter::create(['slug' => $slug, 'kind' => 'recitation', 'style' => $style, 'riwayah' => "Hafs 'an 'Asim", 'is_active' => true]);
        $recording->sources()->create(['provider' => 'mp3quran', 'kind' => 'surah', 'source_key' => $slug, 'base_url' => 'https://audio.example/'.$slug.'/']);
        $recording->syncTranslations(['name' => ['ar' => 'القارئ '.$slug, 'en' => $slug]]);

        return $recording->refresh();
    }

    private function list(array $query = [])
    {
        return $this->actingAs($this->createAdminUser())
            ->getJson(route('admin.reciters.recitations.index', ['reciter' => $this->recording->id, ...$query]));
    }

    public function test_show_page_renders_hero_stats_and_sibling_recordings(): void
    {
        app()->setLocale('ar');

        $this->actingAs($this->createAdminUser())
            ->get(route('admin.reciters.show', $this->recording->id))
            ->assertOk()
            ->assertSee('5 / 30')
            ->assertSee('0:50')
            ->assertSee('recitationManager(', false)
            ->assertDontSee('سورة 1<', false) // surah rows are lazy-loaded, not server-rendered
            ->assertSee(__('quran.recordings.murattal'))
            ->assertDontSee('>mujawwad<', false);
    }

    public function test_endpoint_paginates_with_canonical_surah_numbers(): void
    {
        $first = $this->list(['per_page' => 20])->assertOk()->assertJson(['total' => 30, 'with_audio' => 5, 'missing' => 25, 'next_page' => 2]);
        $this->assertSame(20, substr_count($first->json('html'), '<li'));
        // Surah id 1 has revelation_order 114; the badge must show 1, in mushaf order.
        $this->assertMatchesRegularExpression('/>\s*1\s*<\/span>/', $first->json('html'));
        $this->assertDoesNotMatchRegularExpression('/>\s*114\s*<\/span>/', $first->json('html'));

        $last = $this->list(['per_page' => 20, 'page' => 2])->assertOk()->assertJson(['next_page' => null]);
        $this->assertSame(10, substr_count($last->json('html'), '<li'));
        $this->assertStringContainsString('سورة 30', $last->json('html'));

        $this->list(['per_page' => 51])->assertUnprocessable();
    }

    public function test_endpoint_filters_by_status_and_query(): void
    {
        $this->list(['status' => 'with_audio'])->assertOk()->assertJson(['total' => 5, 'next_page' => null]);
        $this->list(['status' => 'missing'])->assertOk()->assertJson(['total' => 25]);

        $byName = $this->list(['q' => 'baqarah'])->assertOk()->assertJson(['total' => 1, 'with_audio' => 1]);
        $this->assertStringContainsString('سورة 2', $byName->json('html'));

        $this->list(['q' => '12'])->assertOk()->assertJson(['total' => 1, 'with_audio' => 0, 'missing' => 1]);
        $this->list(['q' => '3', 'status' => 'missing'])->assertOk()->assertJson(['total' => 0]);
    }

    public function test_endpoint_rejects_a_source_of_another_recording(): void
    {
        $foreign = Reciter::where('slug', 'husary-murattal')->firstOrFail()->sources->first();

        $this->list(['source' => $foreign->id])->assertNotFound();
        $this->list(['source' => $this->recording->sources->first()->id])->assertOk()->assertJson(['with_audio' => 5]);
    }
}
