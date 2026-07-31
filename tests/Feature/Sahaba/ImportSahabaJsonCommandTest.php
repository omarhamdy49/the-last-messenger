<?php

declare(strict_types=1);

namespace Tests\Feature\Sahaba;

use App\Domain\Genealogy\Models\Relationship;
use App\Domain\Sahaba\Models\Sahabi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ImportSahabaJsonCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/sahaba-json-'.uniqid());
        File::ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_it_imports_out_of_order_identities_and_family_relations_idempotently(): void
    {
        $this->writeJson('00002.json', $this->record(
            externalId: 'muslimscholars:2',
            slug: 'first-sahabi',
            name: 'First Sahabi',
            lineage: [
                'father_ref' => 'muslimscholars:9000',
                'father_name' => 'Missing Father',
                'mother_ref' => null,
                'mother_name' => null,
            ],
            relationships: [[
                'type' => 'sibling',
                'related_ref' => 'muslimscholars:3',
                'related_name' => 'Second Sahabi',
            ]],
            children: [[
                'external_id' => 'muslimscholars:8000',
                'name' => 'Missing Child',
            ]],
        ));

        $this->writeJson('00003.json', $this->record(
            externalId: 'muslimscholars:3',
            slug: 'second-sahabi',
            name: 'Second Sahabi',
        ));

        $this->artisan('sahaba:import-json', ['path' => $this->directory])
            ->assertSuccessful();

        $first = Sahabi::where('external_id', 'muslimscholars:2')->firstOrFail();
        $second = Sahabi::where('external_id', 'muslimscholars:3')->firstOrFail();
        $father = Sahabi::where('external_id', 'muslimscholars:9000')->firstOrFail();
        $child = Sahabi::where('external_id', 'muslimscholars:8000')->firstOrFail();

        $this->assertTrue($first->is_sahabi);
        $this->assertSame(
            'https://muslimscholars.info/manage.php?submit=scholar&ID=2',
            $first->source_url,
        );
        $this->assertTrue($second->is_sahabi);
        $this->assertFalse($father->is_sahabi);
        $this->assertSame(
            'https://muslimscholars.info/manage.php?submit=scholar&ID=9000',
            $father->source_url,
        );
        $this->assertSame($father->id, $first->father_id);
        $this->assertSame($first->id, $child->father_id);
        $this->assertDatabaseHas('relationships', [
            'person_id' => min($first->id, $second->id),
            'related_person_id' => max($first->id, $second->id),
            'relationship_type' => 'sibling',
        ]);

        $this->artisan('sahaba:import-json', ['path' => $this->directory])
            ->assertSuccessful();

        $this->assertSame(4, Sahabi::count());
        $this->assertSame(1, Relationship::count());
        $this->assertSame(1, Sahabi::where('external_id', 'muslimscholars:2')->count());
    }

    public function test_dry_run_validates_without_writing(): void
    {
        $this->writeJson('00002.json', $this->record(
            externalId: 'muslimscholars:2',
            slug: 'dry-run-sahabi',
            name: 'Dry Run Sahabi',
        ));

        $this->artisan('sahaba:import-json', [
            'path' => $this->directory,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, Sahabi::count());
    }

    public function test_existing_only_enriches_a_genealogy_placeholder_from_a_scholar_record(): void
    {
        Sahabi::create([
            'external_id' => 'muslimscholars:1',
            'slug' => 'prophet-placeholder',
            'gender' => 'male',
            'person_type' => 'family',
            'is_sahabi' => false,
        ]);

        $prophet = $this->record(
            externalId: 'muslimscholars:1',
            slug: 'prophet-muhammad',
            name: 'Prophet Muhammad',
        );
        $prophet['entity'] = 'scholar';
        $prophet['classification']['is_sahabi'] = false;
        $prophet['classification']['person_type'] = 'prophet';
        $prophet['translations']['ar']['name'] = 'محمد ﷺ';
        $prophet['translations']['en']['biography'] = 'Trusted English biography.';
        $this->writeJson('00001.json', $prophet);

        $unrelated = $this->record(
            externalId: 'muslimscholars:999',
            slug: 'unrelated-scholar',
            name: 'Unrelated Scholar',
        );
        $unrelated['entity'] = 'scholar';
        $unrelated['classification']['is_sahabi'] = false;
        $unrelated['classification']['person_type'] = 'historical_figure';
        $this->writeJson('00999.json', $unrelated);

        $this->artisan('sahaba:import-json', [
            'path' => $this->directory,
            '--existing-only' => true,
        ])->assertSuccessful();

        $saved = Sahabi::where('external_id', 'muslimscholars:1')->firstOrFail();
        $this->assertSame(1, Sahabi::count());
        $this->assertSame('prophet', $saved->person_type->value);
        $this->assertSame('محمد ﷺ', $saved->getTranslation('name', 'ar', false));
        $this->assertSame('Prophet Muhammad', $saved->getTranslation('name', 'en', false));
        $this->assertSame('Trusted English biography.', $saved->getTranslation('biography', 'en', false));
    }

    public function test_distinct_external_ids_are_not_merged_when_names_match(): void
    {
        $this->writeJson('00002.json', $this->record(
            externalId: 'muslimscholars:2',
            slug: 'parent-sahabi',
            name: 'Parent Sahabi',
            children: [
                ['external_id' => 'muslimscholars:8000', 'name' => 'Shared Name'],
                ['external_id' => 'muslimscholars:8001', 'name' => 'Shared Name'],
            ],
        ));

        $this->artisan('sahaba:import-json', ['path' => $this->directory])
            ->assertSuccessful();

        $parent = Sahabi::where('external_id', 'muslimscholars:2')->firstOrFail();
        $firstChild = Sahabi::where('external_id', 'muslimscholars:8000')->firstOrFail();
        $secondChild = Sahabi::where('external_id', 'muslimscholars:8001')->firstOrFail();

        $this->assertNotSame($firstChild->id, $secondChild->id);
        $this->assertSame($parent->id, $firstChild->father_id);
        $this->assertSame($parent->id, $secondChild->father_id);
    }

    public function test_relation_conflicts_are_preserved_in_the_json_report(): void
    {
        $sharedChild = [['external_id' => 'muslimscholars:8000', 'name' => 'Shared Child']];
        $this->writeJson('00002.json', $this->record(
            externalId: 'muslimscholars:2',
            slug: 'first-parent',
            name: 'First Parent',
            children: $sharedChild,
        ));
        $this->writeJson('00003.json', $this->record(
            externalId: 'muslimscholars:3',
            slug: 'second-parent',
            name: 'Second Parent',
            children: $sharedChild,
        ));
        $reportPath = $this->directory.'/_import-report.json';

        $this->artisan('sahaba:import-json', [
            'path' => $this->directory,
            '--report' => $reportPath,
        ])->assertSuccessful();

        $report = json_decode(File::get($reportPath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $report['relation_conflicts']);
        $this->assertSame(
            'muslimscholars:8000',
            $report['relation_conflict_details'][0]['child_external_id'],
        );
        $this->assertSame(
            'muslimscholars:3',
            $report['relation_conflict_details'][0]['proposed_parent_external_id'],
        );
    }

    public function test_unknown_relation_name_does_not_create_a_fake_identity(): void
    {
        $this->writeJson('00002.json', $this->record(
            externalId: 'muslimscholars:2',
            slug: 'known-parent',
            name: 'Known Parent',
            children: [['external_id' => null, 'name' => 'Unknown']],
        ));

        $this->artisan('sahaba:import-json', ['path' => $this->directory])
            ->assertSuccessful();

        $this->assertSame(1, Sahabi::query()->count());
        $this->assertSame(0, Sahabi::query()->where('external_id', 'like', 'derived-name:%')->count());
    }

    public function test_name_only_relation_does_not_invent_a_source_url(): void
    {
        $this->writeJson('00002.json', $this->record(
            externalId: 'muslimscholars:2',
            slug: 'known-parent',
            name: 'Known Parent',
            children: [['external_id' => null, 'name' => 'Known Name Only Child']],
        ));

        $this->artisan('sahaba:import-json', ['path' => $this->directory])
            ->assertSuccessful();

        $placeholder = Sahabi::query()
            ->where('external_id', 'like', 'derived-name:%')
            ->firstOrFail();
        $this->assertNull($placeholder->source_url);
    }

    /**
     * @param  array<string, mixed>  $lineage
     * @param  array<int, array<string, mixed>>  $relationships
     * @param  array<int, array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private function record(
        string $externalId,
        string $slug,
        string $name,
        array $lineage = [],
        array $relationships = [],
        array $children = [],
    ): array {
        $sourceExternalId = explode(':', $externalId, 2)[1] ?? $externalId;

        return [
            'schema_version' => '1.0',
            'entity' => 'sahabi',
            'external_id' => $externalId,
            'acquisition' => [
                'source' => 'muslimscholars.info',
                'source_external_id' => $sourceExternalId,
                'source_url' => "https://muslimscholars.info/manage.php?submit=scholar&ID={$sourceExternalId}",
            ],
            'slug' => $slug,
            'classification' => [
                'is_sahabi' => true,
                'person_type' => 'companion_male',
                'gender' => 'male',
                'status' => 'prophet_companion',
                'generation' => 1,
                'is_verified' => true,
            ],
            'translations' => [
                'ar' => ['name' => 'صحابي تجريبي'],
                'en' => ['name' => $name, 'biography' => 'Biography'],
            ],
            'life' => [
                'birth_date_hijri' => null,
                'death_date_hijri' => null,
                'birth_date_gregorian' => null,
                'death_date_gregorian' => null,
                'age_at_death' => null,
            ],
            'lineage' => $lineage,
            'tribal_affiliation' => ['tribe' => null, 'clan' => null],
            'islamic_history' => ['roles' => ['Narrator'], 'hadith_count' => null],
            'relationships' => $relationships,
            'genealogy_hints' => ['children' => $children],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeJson(string $filename, array $data): void
    {
        File::put(
            $this->directory.DIRECTORY_SEPARATOR.$filename,
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
    }
}
