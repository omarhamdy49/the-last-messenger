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
     * Atomically reserve $cost quota slots for ($userId, $period) if the user
     * would still be at or under $limit afterwards. Returns true when the slots
     * were reserved (caller may proceed to the paid LLM call), false when the
     * quota would be exceeded. A detailed answer reserves 2; a normal one 1.
     *
     * Race-safe: the check-and-increment happens under a row lock in a short,
     * local DB transaction — the lock is NOT held across the LLM network call.
     */
    public function tryReserve(int $userId, string $period, int $limit, int $cost = 1): bool;

    /**
     * Return previously reserved slots (e.g. the LLM call failed). Never drops
     * below zero. Idempotency is the caller's responsibility — call exactly once
     * per failed reservation, with the same $cost that was reserved.
     */
    public function refund(int $userId, string $period, int $cost = 1): void;

    /**
     * Slots used by ($userId, $period). Used for the "N of 10 left" UI badge.
     */
    public function used(int $userId, string $period): int;

    /**
     * Delete monthly counter rows for periods strictly before $period (format
     * 'Y-m'). Only the current month is needed to enforce quota; older rows are
     * disposable. With $dryRun, returns the count that would be deleted. Used by
     * the scheduled `quran:ai-prune` job to keep this table flat.
     */
    public function purgePeriodsBefore(string $period, bool $dryRun = false): int;
}
