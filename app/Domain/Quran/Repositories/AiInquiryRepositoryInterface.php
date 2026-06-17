<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\AiInquiry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Persistence for "Ask Quran" AI inquiries — the durable history, cost ledger,
 * and admin moderation surface. Narrow by intent rather than extending the
 * generic CRUD RepositoryInterface.
 */
interface AiInquiryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AiInquiry;

    public function findById(int $id): ?AiInquiry;

    /**
     * Update columns on one inquiry (admin moderation). Returns true if a row changed.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Soft-delete one inquiry (preserves cost/audit history).
     */
    public function delete(int $id): bool;

    /**
     * Most recent durable answer for a normalized question in a locale, used as
     * a persistence-backed fallback for the cross-user answer cache. Excludes
     * soft-deleted (moderated-away) rows.
     */
    public function findLatestAnswer(string $questionHash, string $locale): ?AiInquiry;

    /**
     * Admin moderation listing (newest first), optionally filtered. Deduplicated
     * to one canonical row per (question_hash, locale) so repeat/cache-hit asks
     * never show as duplicate questions.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /**
     * A user's own ask history, deduplicated to one entry per question (latest),
     * newest first — for the ChatGPT-style history aside.
     *
     * @return Collection<int, AiInquiry>
     */
    public function historyForUser(int $userId, int $limit = 30): Collection;

    /**
     * Fetch one inquiry owned by the given user (IDOR-safe history fetch).
     */
    public function findForUser(int $id, int $userId): ?AiInquiry;
}
