<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\AiInquiry;
use App\Domain\Quran\Models\AiInquiryUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PruneAiInquiriesTest extends TestCase
{
    use RefreshDatabase;

    /** Create an inquiry with a backdated created_at and optional trashed deleted_at. */
    private function makeInquiry(int $userId, Carbon $createdAt, ?Carbon $deletedAt = null): int
    {
        $id = AiInquiry::create([
            'user_id' => $userId,
            'locale' => 'en',
            'question' => 'q '.uniqid(),
            'normalized_question' => 'q',
            'question_hash' => hash('sha256', uniqid('', true)),
            'answer' => 'a',
            'source' => AiInquiry::SOURCE_LLM,
        ])->id;

        // Set timestamps directly so model timestamp management does not override them.
        DB::table('ai_inquiries')->where('id', $id)->update([
            'created_at' => $createdAt,
            'deleted_at' => $deletedAt,
        ]);

        return $id;
    }

    public function test_prunes_old_trashed_and_stale_quota_but_keeps_live_history_by_default(): void
    {
        config()->set('services.quran_ai.trashed_retention_days', 30);
        config()->set('services.quran_ai.history_retention_days', 0); // retention disabled
        config()->set('services.quran_ai.usage_retention_months', 12);

        $user = User::factory()->create();
        $now = Carbon::now();

        $oldTrashed = $this->makeInquiry($user->id, $now->copy()->subDays(100), $now->copy()->subDays(40));
        $recentTrashed = $this->makeInquiry($user->id, $now->copy()->subDays(100), $now->copy()->subDays(10));
        $oldLive = $this->makeInquiry($user->id, $now->copy()->subDays(500));

        AiInquiryUsage::create(['user_id' => $user->id, 'period' => $now->copy()->subMonths(18)->format('Y-m'), 'count' => 5]);
        AiInquiryUsage::create(['user_id' => $user->id, 'period' => $now->format('Y-m'), 'count' => 3]);

        $this->artisan('quran:ai-prune')->assertSuccessful();

        $this->assertNull(AiInquiry::withTrashed()->find($oldTrashed), 'old trashed must be hard-deleted');
        $this->assertNotNull(AiInquiry::withTrashed()->find($recentTrashed), 'recent trashed must be kept');
        $this->assertNotNull(AiInquiry::find($oldLive), 'live history must be preserved when retention is disabled');
        $this->assertDatabaseCount('ai_inquiry_usages', 1); // only the current month remains
    }

    public function test_history_retention_window_hard_deletes_old_live_rows(): void
    {
        config()->set('services.quran_ai.history_retention_days', 365);

        $user = User::factory()->create();
        $now = Carbon::now();

        $old = $this->makeInquiry($user->id, $now->copy()->subDays(400));
        $recent = $this->makeInquiry($user->id, $now->copy()->subDays(10));

        $this->artisan('quran:ai-prune')->assertSuccessful();

        $this->assertNull(AiInquiry::withTrashed()->find($old), 'history older than the window must be hard-deleted');
        $this->assertNotNull(AiInquiry::find($recent), 'recent history must be kept');
    }

    public function test_dry_run_deletes_nothing(): void
    {
        config()->set('services.quran_ai.trashed_retention_days', 30);
        config()->set('services.quran_ai.history_retention_days', 365);

        $user = User::factory()->create();
        $now = Carbon::now();

        $oldTrashed = $this->makeInquiry($user->id, $now->copy()->subDays(100), $now->copy()->subDays(40));
        $oldLive = $this->makeInquiry($user->id, $now->copy()->subDays(400));

        $this->artisan('quran:ai-prune', ['--dry-run' => true])->assertSuccessful();

        $this->assertNotNull(AiInquiry::withTrashed()->find($oldTrashed), 'dry-run must not delete');
        $this->assertNotNull(AiInquiry::find($oldLive), 'dry-run must not delete');
    }
}
