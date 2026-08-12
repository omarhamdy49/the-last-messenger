<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Shared\Models\AdminLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers admin-logs:prune — the nightly 7-day retention on the activity log.
 * The cutoff is the whole point: prune too far and the Activity Logs page goes
 * blank; prune too little and the table never stops growing.
 */
final class PruneAdminLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_logs_past_the_retention_window_and_keeps_the_rest(): void
    {
        $user = User::factory()->create();

        $this->makeLog($user, 8);   // outside 7 days
        $this->makeLog($user, 30);  // well outside
        $recent = $this->makeLog($user, 6);   // inside — must survive
        $today = $this->makeLog($user, 0);

        $this->artisan('admin-logs:prune')->assertSuccessful();

        $this->assertSame(2, AdminLog::count());
        $this->assertTrue(AdminLog::whereKey($recent->id)->exists());
        $this->assertTrue(AdminLog::whereKey($today->id)->exists());
    }

    public function test_the_window_is_overridable(): void
    {
        $user = User::factory()->create();
        $this->makeLog($user, 6);
        $this->makeLog($user, 2);

        $this->artisan('admin-logs:prune', ['--days' => 3])->assertSuccessful();

        $this->assertSame(1, AdminLog::count());
    }

    private function makeLog(User $user, int $daysAgo): AdminLog
    {
        $log = AdminLog::create([
            'user_id' => $user->id,
            'action' => 'update',
            'model_type' => 'Article',
            'model_id' => 1,
        ]);

        // created_at is set by the model, so age it after the fact.
        $log->forceFill(['created_at' => now()->subDays($daysAgo)->subMinute()])->save();

        return $log;
    }
}
