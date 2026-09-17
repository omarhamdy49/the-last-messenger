<?php

declare(strict_types=1);

namespace Tests\Feature\Seerah;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahMilestone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The two composite indexes on seerah_events ship only because these query
 * shapes use them (constitution II: no speculative indexes).
 */
class QueryPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $milestoneIds = collect(range(1, 10))
            ->map(fn (int $i): int => SeerahMilestone::query()->create(['slug' => "m{$i}", 'order' => $i])->id);
        $typeId = DB::table('seerah_event_types')->insertGetId(['slug' => 'other', 'locale' => json_encode(['ar' => 'آخر'])]);
        $eras = ['pre_birth', 'childhood', 'pre_revelation', 'mecca', 'medina'];

        DB::table('seerah_events')->insert(array_map(fn (int $i): array => [
            'slug' => "event-{$i}",
            'event_type' => $typeId,
            'sm_id' => $milestoneIds[$i % 10],
            'order' => $i,
            'status' => $i % 5 === 0 ? 'draft' : 'published',
            'era' => $eras[$i % 5],
        ], range(1, 300)));

        DB::statement('ANALYZE TABLE seerah_events');
    }

    public function test_milestone_listing_uses_the_milestone_status_order_index(): void
    {
        $query = SeerahEvent::query()->select(['id', 'slug'])
            ->where('sm_id', SeerahMilestone::query()->value('id'))
            ->where('status', 'published')
            ->orderBy('order');

        $this->assertContains('se_sm_status_order_idx', $this->indexesUsed($query));
    }

    public function test_directory_era_filter_uses_the_status_era_index(): void
    {
        $query = SeerahEvent::query()->select(['id', 'slug'])
            ->where('status', 'published')
            ->where('era', 'medina');

        $this->assertContains('se_status_era_idx', $this->indexesUsed($query));
    }

    /**
     * @return array<int, string>
     */
    private function indexesUsed(Builder $query): array
    {
        $plan = DB::select('EXPLAIN '.$query->toSql(), $query->getBindings());

        return collect($plan)->pluck('key')->filter()->flatMap(fn (string $key) => explode(',', $key))->all();
    }
}
