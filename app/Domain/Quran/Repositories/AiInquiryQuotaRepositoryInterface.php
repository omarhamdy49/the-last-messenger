<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

/**
 * Per-user monthly quota counter for the "Ask Quran" AI feature.
 *
 * Deliberately a narrow interface (not the generic CRUD RepositoryInterface):
 * the only operations that exist are "reserve a slot", "give one back", and
 * "how many used". Narrow contract → fewer ways to misuse the quota.
 */
interface AiInquiryQuotaRepositoryInterface
{
    /**
     * Atomically reserve one quota slot for ($userId, $period) if the user is
     * still under $limit. Returns true when a slot was reserved (caller may
     * proceed to the paid LLM call), false when the quota is exhausted.
     *
     * Race-safe: the check-and-increment happens under a row lock in a short,
     * local DB transaction — the lock is NOT held across the LLM network call.
     */
    public function tryReserve(int $userId, string $period, int $limit): bool;

    /**
     * Return a previously reserved slot (e.g. the LLM call failed). Never drops
     * below zero. Idempotency is the caller's responsibility — call exactly once
     * per failed reservation.
     */
    public function refund(int $userId, string $period): void;

    /**
     * Slots used by ($userId, $period). Used for the "N of 10 left" UI badge.
     */
    public function used(int $userId, string $period): int;
}
