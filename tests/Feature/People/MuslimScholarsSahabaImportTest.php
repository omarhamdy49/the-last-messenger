<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Domain\People\Import\MuslimScholarsSahabaImportService;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimSource;
use Database\Seeders\IslamicRoleSeeder;
use Database\Seeders\ProphetFamilySeeder;
use Database\Seeders\ProphetLineageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MuslimScholarsSahabaImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private const HEADERS = [
        'source_id', 'source_url', 'source_status', 'action',
        'existing_muslim_id', 'existing_match_reason', 'slug', 'thumb',
        'name_ar', 'name_en', 'kunya', 'bio', 'content', 'birth_place',
        'gender', 'birth_year_hijri', 'death_year_hijri', 'generation',
        'search_name_ar', 'search_name_en', 'father_id', 'mother_id',
        'tree_display_order', 'hide_from_tree', 'is_published', 'role_slug',
        'role_is_featured', 'role_sort_order', 'source_lineage_en',
        'source_birth_raw', 'source_death_raw', 'source_interests',
        'source_tags', 'source_resources',
    ];

    public function test_new_person_is_imported_as_an_unpublished_provenanced_draft_and_rerun_refreshes_it(): void
    {
        IslamicRole::query()->create([
            'slug' => 'sahabi',
            'name_ar' => 'صحابي',
            'name_en' => 'Companion',
        ]);

        $path = $this->csv([$this->row()]);

        try {
            $arguments = ['--path' => $path, '--expected' => 1, '--force' => true];
            $this->artisan('muslims:import', $arguments)->assertSuccessful();
            $this->artisan('muslims:import', $arguments)->assertSuccessful();

            $this->assertDatabaseCount('muslims', 1);
            $this->assertDatabaseCount('muslims_roles', 1);
            $this->assertDatabaseCount('muslim_sources', 1);
            $this->assertDatabaseHas('muslims', [
                'slug' => 'abu-bakr-as-siddique-msi-2',
                'name_ar' => 'أبو بكر الصديق',
                'gender' => 'male',
                'is_published' => false,
                'generation' => 'companion',
            ]);
            $this->assertDatabaseHas('muslim_sources', [
                'source' => MuslimScholarsSahabaImportService::SOURCE,
                'external_id' => '2',
            ]);
            $muslim = Muslim::query()->sole();
            $this->assertSame(['sahabi'], $muslim->roles()->pluck('slug')->all());
            $this->assertSame(
                'أبو بكر الصديق',
                $muslim->profile->localized('full_name', 'ar'),
            );
            $this->assertSame(['al-Isabah'], array_column(
                MuslimSource::query()->sole()->metadata['resources'],
                'name',
            ));
        } finally {
            @unlink($path);
        }
    }

    public function test_stable_slug_mapping_updates_existing_and_ignores_stale_numeric_id_while_homonym_stays_distinct(): void
    {
        IslamicRole::query()->create([
            'slug' => 'sahabi',
            'name_ar' => 'صحابي',
            'name_en' => 'Companion',
        ]);
        $umar = Muslim::query()->create([
            'slug' => 'umar-ibn-al-khattab',
            'name_ar' => 'عمر بن الخطاب',
            'name_en' => 'Umar ibn al-Khattab',
            'kunya' => ['ar' => 'أبو حفص'],
            'is_published' => true,
        ]);

        $path = $this->csv([
            $this->row([
                'source_id' => '3',
                'source_url' => 'https://muslimscholars.info/manage.php?submit=scholar&ID=3',
                'action' => 'review_existing',
                'existing_muslim_id' => '999999',
                'existing_match_reason' => 'arabic_prefix',
                'slug' => 'umar-ibn-al-khattab-msi-3',
                'name_ar' => 'عمر بن الخطاب بن نفيل',
                'name_en' => "'Umar ibn al-Khattab",
                'kunya' => '{"en":"Abu Hafs, al-Farooq"}',
                'content' => '{"en":"Umar bin al-Khattab bin Nufayl"}',
            ]),
            $this->row([
                'source_id' => '4741',
                'source_url' => 'https://muslimscholars.info/manage.php?submit=scholar&ID=4741',
                'action' => 'review_existing',
                'existing_muslim_id' => '999999',
                'existing_match_reason' => 'arabic_prefix',
                'slug' => 'abdullah-bin-umar-al-jrmy-msi-4741',
                'name_ar' => 'عبد الله بن عمر الجرمي',
                'name_en' => "'Abdullah bin 'Umar al-Jrmy",
            ]),
        ]);

        try {
            $arguments = [
                '--path' => $path,
                '--expected' => 2,
                '--force' => true,
            ];
            $this->artisan('muslims:import', $arguments)->assertSuccessful();
            $this->artisan('muslims:import', $arguments)->assertSuccessful();

            $this->assertDatabaseCount('muslims', 2);
            $this->assertDatabaseCount('muslim_sources', 2);
            $this->assertDatabaseHas('muslims', [
                'id' => $umar->id,
                'name_ar' => 'عمر بن الخطاب بن نفيل',
                'gender' => 'male',
                'generation' => 'companion',
                'is_published' => true,
            ]);
            $this->assertDatabaseHas('muslims', [
                'slug' => 'abdullah-bin-umar-al-jrmy-msi-4741',
                'gender' => 'male',
                'is_published' => false,
            ]);

            $umar->refresh();
            $this->assertSame('أبو حفص', $umar->getTranslation('kunya', 'ar', false));
            $this->assertSame('Abu Hafs, al-Farooq', $umar->getTranslation('kunya', 'en', false));
            $this->assertSame(
                'Umar bin al-Khattab bin Nufayl',
                $umar->getTranslation('content', 'en', false),
            );
            $this->assertSame(
                'عمر بن الخطاب بن نفيل',
                $umar->profile->localized('full_name', 'ar'),
            );
            $this->assertSame(
                ['en' => 'Abu Hafs, al-Farooq'],
                MuslimSource::query()->where('external_id', '3')->sole()->metadata['kunya'],
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_unique_exact_name_updates_an_existing_companion_without_an_explicit_map(): void
    {
        IslamicRole::query()->create([
            'slug' => 'sahabi',
            'name_ar' => 'صحابي',
            'name_en' => 'Companion',
        ]);
        $existing = Muslim::query()->create([
            'slug' => 'existing-abu-bakr',
            'name_ar' => 'أبو بكر الصديق',
            'name_en' => 'Abu Bakr As-Siddique',
            'generation' => 'companion',
            'is_published' => true,
        ]);
        $path = $this->csv([$this->row()]);

        try {
            $this->artisan('muslims:import', [
                '--path' => $path,
                '--expected' => 1,
                '--force' => true,
            ])->assertSuccessful();

            $this->assertDatabaseCount('muslims', 1);
            $this->assertDatabaseHas('muslim_sources', [
                'muslim_id' => $existing->id,
                'external_id' => '2',
            ]);
            $this->assertTrue($existing->fresh()->is_published);
        } finally {
            @unlink($path);
        }
    }

    public function test_reviewed_map_covers_current_seeded_people_without_merging_known_homonyms(): void
    {
        // Asserts row-for-row against the reviewed 7,806-row export, which lives
        // under storage/app (gitignored) rather than in the repo. Whoever holds
        // the file gets the check; a fresh clone and CI skip it instead of
        // failing on data they were never given.
        $csv = base_path('storage/app/sahaba-import-preview/muslimscholars-sahaba-mapped-preview.csv');

        if (! is_readable($csv)) {
            $this->markTestSkipped("Reviewed import export not present at {$csv}.");
        }

        $this->seed([
            IslamicRoleSeeder::class,
            ProphetLineageSeeder::class,
            ProphetFamilySeeder::class,
        ]);

        $service = app(MuslimScholarsSahabaImportService::class);
        $preview = $service->preview($csv);
        $plan = $service->plan($preview);

        $this->assertSame(7806, $plan->total());
        $this->assertSame(49, count($plan->mergeRows));
        $this->assertSame(7757, count($plan->createRows));
        $this->assertSame([], $plan->refreshRows);

        $mergedSourceIds = array_column(array_column($plan->mergeRows, 'row'), 'source_id');
        $createdSourceIds = array_column($plan->createRows, 'source_id');
        $this->assertContains('54', $mergedSourceIds);
        $this->assertContains('17', $mergedSourceIds);
        $this->assertContains('516', $mergedSourceIds);
        $this->assertContains('6491', $createdSourceIds);
        $this->assertContains('9372', $createdSourceIds);
    }

    public function test_dry_run_validates_without_writing(): void
    {
        $path = $this->csv([$this->row()]);

        try {
            $this->artisan('muslims:import', [
                '--path' => $path,
                '--expected' => 1,
                '--dry-run' => true,
            ])->assertSuccessful();

            $this->assertDatabaseCount('muslims', 0);
            $this->assertDatabaseCount('muslim_sources', 0);
        } finally {
            @unlink($path);
        }
    }

    /** @param list<array<string, string>> $rows */
    private function csv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sahaba-import-');
        self::assertIsString($path);
        $handle = fopen($path, 'wb');
        self::assertIsResource($handle);
        fputcsv($handle, self::HEADERS, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(
                static fn (string $header): string => $row[$header] ?? '',
                self::HEADERS,
            ), ',', '"', '');
        }
        fclose($handle);

        return $path;
    }

    /** @param array<string, string> $overrides @return array<string, string> */
    private function row(array $overrides = []): array
    {
        return array_replace([
            'source_id' => '2',
            'source_url' => 'https://muslimscholars.info/manage.php?submit=scholar&ID=2',
            'source_status' => 'Comp.(RA)',
            'action' => 'insert_draft',
            'slug' => 'abu-bakr-as-siddique-msi-2',
            'name_ar' => 'أبو بكر الصديق',
            'name_en' => 'Abu Bakr As-Siddique',
            'kunya' => '{"en":"Abu Bakr"}',
            'content' => '{"en":"Abdullah bin Abi Qahafah"}',
            'birth_place' => '{"en":"Makkah"}',
            'birth_year_hijri' => '-51',
            'death_year_hijri' => '13',
            'generation' => 'companion',
            'search_name_ar' => 'ابو بكر الصديق',
            'search_name_en' => 'abu bakr as siddique',
            'hide_from_tree' => '0',
            'is_published' => '0',
            'role_slug' => 'sahabi',
            'role_is_featured' => '0',
            'role_sort_order' => '0',
            'source_lineage_en' => 'Abdullah bin Abi Qahafah',
            'source_birth_raw' => '51 BH/573 CE (Makkah)',
            'source_death_raw' => '13 AH/634 CE (Medina)',
            'source_interests' => 'Tafsir/Quran',
            'source_tags' => 'Quraish|Early Muslim',
            'source_resources' => '[{"name":"al-Isabah","url":"https://muslimscholars.info/resource"}]',
        ], $overrides);
    }
}
