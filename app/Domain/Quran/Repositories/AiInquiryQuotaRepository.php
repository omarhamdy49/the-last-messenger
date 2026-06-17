<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\AiInquiryUsage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class AiInquiryQuotaRepository implements AiInquiryQuotaRepositoryInterface
{
    public function tryReserve(int $userId, string $period, int $limit, int $cost = 1): bool
    {
        // A non-positive limit means the feature is effectively disabled; a
        // non-positive cost is a programming error we refuse rather than crash.
        if ($limit <= 0 || $cost <= 0) {
            return false;
        }

        $this->ensureRow($userId, $period);

        // Short, local transaction: lock the single counter row, re-check the
        // limit under the lock, then increment by $cost. Two concurrent requests
        // that would together exceed the limit serialize here, so at most one
        // wins. The lock is released the moment this commits — well before the
        // LLM call runs.
        return DB::transaction(function () use ($userId, $period, $limit, $cost): bool {
            $row = AiInquiryUsage::query()
                ->where('user_id', $userId)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if ($row === null || $row->count + $cost > $limit) {
                return false;
            }

            $row->increment('count', $cost);

            return true;
        });
    }

    public function refund(int $userId, string $period, int $cost = 1): void
    {
        if ($cost <= 0) {
            return;
        }

        // Lock + clamp so a refund can never push the counter below zero, even if
        // it is called with a larger $cost than was actually reserved.
        DB::transaction(function () use ($userId, $period, $cost): void {
            $row = AiInquiryUsage::query()
                ->where('user_id', $userId)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                return;
            }

            $row->count = max(0, $row->count - $cost);
            $row->save();
        });
    }

    public function used(int $userId, string $period): int
    {
        return (int) (AiInquiryUsage::query()
            ->where('user_id', $userId)
            ->where('period', $period)
            ->value('count') ?? 0);
    }

    public function purgePeriodsBefore(string $period, bool $dryRun = false): int
    {
        // 'Y-m' strings sort lexicographically, so a string comparison is a correct
        // chronological filter. Rows are tiny and few, so no batching is needed.
        $query = AiInquiryUsage::query()->where('period', '<', $period);

        return $dryRun ? $query->count() : $query->delete();
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
