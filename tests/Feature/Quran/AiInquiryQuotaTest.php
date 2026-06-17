<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Repositories\AiInquiryQuotaRepositoryInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiInquiryQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): AiInquiryQuotaRepositoryInterface
    {
        return app(AiInquiryQuotaRepositoryInterface::class);
    }

    public function test_reserves_up_to_the_limit_then_refuses(): void
    {
        $user = User::factory()->create();
        $repo = $this->repo();
        $period = '2026-06';

        for ($i = 1; $i <= 10; $i++) {
            $this->assertTrue($repo->tryReserve($user->id, $period, 10), "reservation {$i} should succeed");
        }

        $this->assertFalse($repo->tryReserve($user->id, $period, 10), '11th reservation must be refused');
        $this->assertSame(10, $repo->used($user->id, $period));
    }

    public function test_refund_returns_a_slot_and_never_goes_negative(): void
    {
        $user = User::factory()->create();
        $repo = $this->repo();
        $period = '2026-06';

        $this->assertTrue($repo->tryReserve($user->id, $period, 1));
        $this->assertFalse($repo->tryReserve($user->id, $period, 1));

        $repo->refund($user->id, $period);
        $this->assertSame(0, $repo->used($user->id, $period));

        // A second, spurious refund must not drive the counter below zero.
        $repo->refund($user->id, $period);
        $this->assertSame(0, $repo->used($user->id, $period));

        $this->assertTrue($repo->tryReserve($user->id, $period, 1), 'slot should be reusable after refund');
    }

    public function test_quota_is_isolated_per_period_and_per_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $repo = $this->repo();

        $this->assertTrue($repo->tryReserve($alice->id, '2026-06', 1));
        $this->assertFalse($repo->tryReserve($alice->id, '2026-06', 1));

        // Next month is a fresh allowance for the same user.
        $this->assertTrue($repo->tryReserve($alice->id, '2026-07', 1));

        // A different user is unaffected by Alice's usage.
        $this->assertTrue($repo->tryReserve($bob->id, '2026-06', 1));
    }

    public function test_non_positive_limit_disables_the_feature(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->repo()->tryReserve($user->id, '2026-06', 0));
        $this->assertSame(0, $this->repo()->used($user->id, '2026-06'));
    }
}
