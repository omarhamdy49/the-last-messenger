<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Console\Commands\Quran\QfBootstrapCommand;
use App\Domain\Quran\Models\Chapter;
use Database\Seeders\QuranMushafSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Quran\Import\Concerns\FakesQuranFoundation;
use Tests\TestCase;

final class QfCommandsTest extends TestCase
{
    use FakesQuranFoundation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpQuranFoundation();
        $this->seed(QuranMushafSeeder::class);
    }

    public function test_doctor_reports_each_capability_without_secrets(): void
    {
        Http::fake(function ($request) {
            $url = urldecode($request->url());

            return match (true) {
                str_contains($url, 'oauth2.test') => Http::response(['access_token' => 'fresh-token', 'expires_in' => 3600]),
                str_contains($url, '/chapters') => Http::response(['chapters' => array_fill(0, 114, ['id' => 1])]),
                str_contains($url, '/hadiths/') => Http::response(['type' => 'not_found'], 404),
                str_contains($url, 'quran-reflect') => Http::response(['type' => 'insufficient_scope'], 403),
                default => null,
            };
        });

        $this->artisan('quran:qf:doctor')
            ->expectsTable(['capability', 'status'], [
                ['token', 'OK'],
                ['content', 'OK'],
                ['hadith', 'not_found'],
                ['reflect', 'insufficient_scope'],
            ])
            ->doesntExpectOutputToContain('secret-xyz')
            ->doesntExpectOutputToContain('fresh-token')
            ->assertSuccessful();
    }

    public function test_doctor_fails_when_the_token_is_refused(): void
    {
        Http::fake(['oauth2.test/*' => Http::response(['type' => 'invalid_client'], 401)]);

        $this->artisan('quran:qf:doctor')->assertFailed();
    }

    public function test_structure_command_exits_zero_on_real_fixtures(): void
    {
        $this->fakeStructure();

        $this->artisan('quran:qf:import-structure')->assertSuccessful();
        $this->assertSame(114, Chapter::query()->count());
    }

    public function test_structure_command_exits_non_zero_when_a_gate_fails(): void
    {
        $this->fakeStructure(['juzs' => ['juzs' => []]]);

        $this->artisan('quran:qf:import-structure')->assertFailed();
    }

    public function test_verses_command_validates_its_range(): void
    {
        $this->artisan('quran:qf:import-verses', ['--from' => 5, '--to' => 2])->assertFailed();
        $this->artisan('quran:qf:import-verses', ['--scripts' => 'klingon', '--from' => 1, '--to' => 1])->assertFailed();
    }

    public function test_download_assets_requires_a_target(): void
    {
        $this->artisan('quran:qf:download-assets')->assertFailed();
    }

    public function test_bootstrap_runs_steps_in_order_and_stops_at_the_first_failure(): void
    {
        // Structure succeeds; verses fails because QF returns nothing for the scripts.
        $this->fakeStructure();

        $this->artisan('quran:qf:bootstrap')
            ->expectsOutputToContain('▶ structure')
            ->expectsOutputToContain('▶ verses')
            ->doesntExpectOutputToContain('▶ words')
            ->assertFailed();

        $this->assertSame(['structure', 'verses', 'words', 'catalogue'], array_keys(QfBootstrapCommand::STEPS));
    }

    public function test_bootstrap_rejects_unknown_steps(): void
    {
        $this->artisan('quran:qf:bootstrap', ['--only' => ['nope']])->assertFailed();
    }

    /**
     * @param  array<string, array<string, mixed>>  $overrides
     */
    private function fakeStructure(array $overrides = []): void
    {
        $bodies = [
            'chapters?language=en' => $this->qfFixture('chapters-en'),
            'chapters?language=ar' => $this->qfFixture('chapters-ar'),
            'chapters?language=fr' => $this->qfFixture('chapters-fr'),
            'chapters?language=tr' => $this->qfFixture('chapters-tr'),
            'chapters?language=ur' => $this->qfFixture('chapters-ur'),
            'juzs' => $this->qfFixture('juzs'),
            'hizbs' => $this->qfFixture('hizbs'),
            'rub_el_hizbs' => $this->qfFixture('rub_el_hizbs'),
            'manzils' => $this->qfFixture('manzils'),
            'rukus' => $this->qfFixture('rukus'),
            'pages?mushaf=1' => $this->qfFixture('pages'),
            'resources/languages' => $this->qfFixture('languages'),
        ];
        foreach ($overrides as $path => $body) {
            $bodies[$path] = $body;
        }

        Http::fake(function ($request) use ($bodies) {
            $path = urldecode(str_replace('https://apis.test/content/api/v4/', '', $request->url()));

            if (isset($bodies[$path])) {
                return Http::response($bodies[$path]);
            }

            // Anything else (e.g. verse scripts) answers with an empty list.
            return Http::response(['verses' => [], 'meta' => []]);
        });
    }
}
