<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\AiInquiryUsage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class AiInquiryQuotaRepository implements AiInquiryQuotaRepositoryInterface
{
    public function tryReserve(int $userId, string $period, int $limit): bool
    {
        // A non-positive limit means the feature is effectively disabled.
        if ($limit <= 0) {
            return false;
        }

        $this->ensureRow($userId, $period);

        // Short, local transaction: lock the single counter row, re-check the
        // limit under the lock, then increment. Two concurrent requests at
        // count = limit-1 serialize here, so exactly one wins. The lock is
        // released the moment this commits — well before the LLM call runs.
        return DB::transaction(function () use ($userId, $period, $limit): bool {
            $row = AiInquiryUsage::query()
                ->where('user_id', $userId)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if ($row === null || $row->count >= $limit) {
                return false;
            }

            $row->increment('count');

            return true;
        });
    }

    public function refund(int $userId, string $period): void
    {
        AiInquiryUsage::query()
            ->where('user_id', $userId)
            ->where('period', $period)
            ->where('count', '>', 0)
            ->decrement('count');
    }

    public function used(int $userId, string $period): int
    {
        return (int) (AiInquiryUsage::query()
            ->where('user_id', $userId)
            ->where('period', $period)
            ->value('count') ?? 0);
    }

    /**
     * Make sure the (user, period) counter row exists before we lock it.
     * firstOrCreate can lose a unique race under concurrency; the duplicate
     * insert simply means the row now exists, which is exactly what we want.
     */
    private function ensureRow(int $userId, string $period): void
    {
        try {
            AiInquiryUsage::query()->firstOrCreate(
                ['user_id' => $userId, 'period' => $period],
                ['count' => 0],
            );
        } catch (QueryException) {
            // Concurrent insert won the unique race — the row exists now. Proceed.
        }
    }
}
