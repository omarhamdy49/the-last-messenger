<?php

declare(strict_types=1);

namespace Tests\Feature\Seerah;

use Database\Seeders\DevContentFixturesSeeder;
use Database\Seeders\SeerahEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Event types moved from a migration into a seeder, so the seeder must be
 * re-runnable without duplicating rows. Events are never seeded.
 */
class SeerahSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_type_seeder_is_idempotent_and_complete(): void
    {
        $this->seed(SeerahEventTypeSeeder::class);
        $this->seed(SeerahEventTypeSeeder::class);

        $types = DB::table('seerah_event_types')->get();

        $this->assertCount(11, $types);

        foreach ($types as $type) {
            $this->assertNotEmpty(json_decode($type->locale, true)['ar'] ?? null, "{$type->slug} has no Arabic name");
            $this->assertNotEmpty(json_decode((string) $type->metadata, true)['icon'] ?? null, "{$type->slug} has no icon");
        }
    }

    public function test_no_seeder_creates_seerah_events(): void
    {
        // Events and milestones are authored in the admin; seeders provide the type lookup only.
        // The fixtures assign the `writer` role, so roles must exist first.
        $this->seedRolesAndPermissions();
        $this->seed(DevContentFixturesSeeder::class);
        $this->seed(SeerahEventTypeSeeder::class);

        $this->assertSame(0, DB::table('seerah_events')->count());
        $this->assertSame(0, DB::table('seerah_milestones')->count());
    }
}
