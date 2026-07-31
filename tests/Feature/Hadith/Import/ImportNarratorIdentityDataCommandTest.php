<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Import;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Models\SahabiTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ImportNarratorIdentityDataCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/narrator-data-'.uniqid());
        File::ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_exact_source_identity_links_distinct_sahabi_scholar_and_hadith_rows(): void
    {
        $sahabi = $this->createSahabi('muslimscholars:2', 'Abu Bakr');
        $profileDirectory = $this->directory.'/profiles';
        File::ensureDirectoryExists($profileDirectory);
        File::put(
            $profileDirectory.'/00002.json',
            json_encode($this->profile('muslimscholars:2', 'Abu Bakr'), JSON_THROW_ON_ERROR),
        );

        $dryReport = $this->directory.'/scholar-dry-run.json';
        $this->artisan('hadith:import-scholars', [
            'path' => $profileDirectory,
            '--dry-run' => true,
            '--report' => $dryReport,
        ])->assertSuccessful();
        $this->assertSame(0, HadithScholar::query()->count());

        $this->artisan('hadith:import-scholars', [
            'path' => $profileDirectory,
            '--report' => $this->directory.'/scholar-import.json',
        ])->assertSuccessful();

        $scholar = HadithScholar::query()->where('external_id', 'muslimscholars:2')->firstOrFail();
        $this->assertSame($sahabi->id, $scholar->sahaba_id);
        $this->assertTrue($scholar->is_companion);
        $this->assertSame(
            'https://muslimscholars.info/manage.php?submit=scholar&ID=2',
            $scholar->source_url,
        );
        $this->assertSame(1, Sahabi::query()->count());
        $this->assertSame(1, HadithScholar::query()->count());

        $hadith = Hadith::factory()->withUrn(1002)->create(['primary_narrator_id' => null]);
        $mappingPath = $this->directory.'/primary_narrator_mappings.jsonl';
        File::put($mappingPath, json_encode([
            'status' => 'matched',
            'sunnah_urn' => 1002,
            'scholar_external_id' => 'muslimscholars:2',
            'source_url' => 'https://sunnah.com/example:2',
            'source_aliases' => ['Abu Bakr'],
        ], JSON_THROW_ON_ERROR)."\n");

        $this->artisan('hadith:apply-primary-narrators', [
            'path' => $mappingPath,
            '--dry-run' => true,
            '--report' => $this->directory.'/mapping-dry-run.json',
        ])->assertSuccessful();
        $this->assertNull($hadith->fresh()->primary_narrator_id);

        $this->artisan('hadith:apply-primary-narrators', [
            'path' => $mappingPath,
            '--report' => $this->directory.'/mapping-import.json',
        ])->assertSuccessful();
        $this->assertSame($scholar->id, $hadith->fresh()->primary_narrator_id);
    }

    public function test_ambiguous_companion_alias_is_reported_and_not_auto_linked(): void
    {
        $first = $this->createSahabi('muslimscholars:10', 'Shared Companion');
        $second = $this->createSahabi('muslimscholars:20', 'Shared Companion');
        $profileDirectory = $this->directory.'/profiles';
        File::ensureDirectoryExists($profileDirectory);
        File::put(
            $profileDirectory.'/00030.json',
            json_encode($this->profile('muslimscholars:30', 'Shared Companion'), JSON_THROW_ON_ERROR),
        );
        $reportPath = $this->directory.'/ambiguous.json';

        $this->artisan('hadith:import-scholars', [
            'path' => $profileDirectory,
            '--report' => $reportPath,
        ])->assertSuccessful();

        $scholar = HadithScholar::query()->where('external_id', 'muslimscholars:30')->firstOrFail();
        $report = json_decode(File::get($reportPath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNull($scholar->sahaba_id);
        $expectedIds = [$first->id, $second->id];
        sort($expectedIds);
        $this->assertSame($expectedIds, $report['ambiguous_sahaba'][0]['candidate_ids']);
    }

    public function test_coverage_audit_reports_null_empty_and_unknown_with_baseline_deltas(): void
    {
        HadithScholar::factory()->create([
            'external_id' => null,
            'full_name' => [],
            'reliability' => 'unknown',
        ]);
        $before = $this->directory.'/before.json';
        $after = $this->directory.'/after.json';

        $this->artisan('hadith:audit-narrator-data', [
            '--phase' => 'before',
            '--output' => $before,
        ])->assertSuccessful();
        $this->artisan('hadith:audit-narrator-data', [
            '--phase' => 'after',
            '--output' => $after,
            '--baseline' => $before,
        ])->assertSuccessful();

        $report = json_decode(File::get($after), true, 512, JSON_THROW_ON_ERROR);
        $columns = $report['tables']['hadith_scholars']['columns'];
        $this->assertSame(1, $columns['external_id']['null']);
        $this->assertSame(1, $columns['full_name']['empty']);
        $this->assertSame(1, $columns['reliability']['unknown']);
        $this->assertSame(0, $report['changes']['hadith_scholars']['rows_delta']);
    }

    public function test_conflicting_mapping_rows_for_one_urn_are_rejected(): void
    {
        $mappingPath = $this->directory.'/conflicting.jsonl';
        $first = [
            'status' => 'matched',
            'sunnah_urn' => 1002,
            'scholar_external_id' => 'muslimscholars:2',
        ];
        $second = [
            'status' => 'matched',
            'sunnah_urn' => 1002,
            'scholar_external_id' => 'muslimscholars:3',
        ];
        File::put(
            $mappingPath,
            json_encode($first, JSON_THROW_ON_ERROR)."\n".json_encode($second, JSON_THROW_ON_ERROR)."\n",
        );

        $this->artisan('hadith:apply-primary-narrators', ['path' => $mappingPath])
            ->expectsOutputToContain('Conflicting narrator mappings for Sunnah URN 1002')
            ->assertFailed();
    }

    public function test_verified_attribution_can_require_a_profile_not_labelled_narrator(): void
    {
        $profileDirectory = $this->directory.'/profiles';
        File::ensureDirectoryExists($profileDirectory);
        $profile = $this->profile('muslimscholars:40', 'Attributed Scholar');
        $profile['classification']['is_sahabi'] = false;
        $profile['narrator_metadata']['is_narrator'] = false;
        File::put(
            $profileDirectory.'/00040.json',
            json_encode($profile, JSON_THROW_ON_ERROR),
        );
        $mappingPath = $this->directory.'/required.jsonl';
        File::put($mappingPath, json_encode([
            'status' => 'matched',
            'sunnah_urn' => 1040,
            'scholar_external_id' => 'muslimscholars:40',
        ], JSON_THROW_ON_ERROR)."\n");

        $this->artisan('hadith:import-scholars', [
            'path' => $profileDirectory,
            '--required-mappings' => $mappingPath,
            '--report' => $this->directory.'/required-import.json',
        ])->assertSuccessful();

        $scholar = HadithScholar::query()->where('external_id', 'muslimscholars:40')->firstOrFail();
        $this->assertFalse($scholar->is_companion);
    }

    public function test_unresolved_mapping_fails_dry_run_and_writes_review_report(): void
    {
        HadithScholar::factory()->create(['external_id' => 'muslimscholars:50']);
        $mappingPath = $this->directory.'/missing-hadith.jsonl';
        $reportPath = $this->directory.'/missing-hadith-report.json';
        File::put($mappingPath, json_encode([
            'status' => 'matched',
            'sunnah_urn' => 999999,
            'scholar_external_id' => 'muslimscholars:50',
        ], JSON_THROW_ON_ERROR)."\n");

        $this->artisan('hadith:apply-primary-narrators', [
            'path' => $mappingPath,
            '--dry-run' => true,
            '--report' => $reportPath,
        ])->assertFailed();

        $report = json_decode(File::get($reportPath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $report['missing_hadith_count']);
    }

    private function createSahabi(string $externalId, string $name): Sahabi
    {
        $sahabi = Sahabi::query()->create([
            'external_id' => $externalId,
            'slug' => str_replace(':', '-', $externalId),
            'gender' => 'male',
            'person_type' => 'sahabi',
            'is_sahabi' => true,
            'generation' => 1,
            'is_verified' => true,
            'reported_hadith_count' => 0,
        ]);
        SahabiTranslation::query()->create([
            'sahabi_id' => $sahabi->id,
            'locale' => 'en',
            'name' => $name,
            'is_ready' => true,
        ]);

        return $sahabi;
    }

    /** @return array<string, mixed> */
    private function profile(string $externalId, string $name): array
    {
        $sourceId = str_replace('muslimscholars:', '', $externalId);

        return [
            'schema_version' => '1.0',
            'entity' => 'scholar',
            'external_id' => $externalId,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'classification' => [
                'is_sahabi' => true,
                'generation' => 1,
            ],
            'translations' => [
                'en' => ['name' => $name, 'biography' => 'Verified source biography.'],
                'ar' => ['name' => 'اسم عربي'],
            ],
            'life' => ['death_date_hijri' => '13 AH'],
            'narrator_metadata' => [
                'is_narrator' => true,
                'generation_label' => 'Comp.(RA) [1st Generation]',
                'reliability' => null,
            ],
            'acquisition' => [
                'source_url' => "https://muslimscholars.info/manage.php?submit=scholar&ID={$sourceId}",
            ],
        ];
    }
}
