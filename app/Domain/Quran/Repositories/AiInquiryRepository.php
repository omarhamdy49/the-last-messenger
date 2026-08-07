<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\AiInquiry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class AiInquiryRepository implements AiInquiryRepositoryInterface
{
    public function create(array $data): AiInquiry
    {
        return AiInquiry::query()->create($data);
    }

    public function findById(int $id): ?AiInquiry
    {
        return AiInquiry::query()->find($id);
    }

    public function update(int $id, array $data): bool
    {
        return AiInquiry::query()->whereKey($id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return AiInquiry::query()->whereKey($id)->delete() > 0;
    }

    public function findLatestAnswer(string $questionHash, string $locale): ?AiInquiry
    {
        return AiInquiry::query()
            ->select(['id', 'answer', 'citations', 'question', 'question_hash', 'locale', 'created_at'])
            ->where('question_hash', $questionHash)
            ->where('locale', $locale)
            ->latest('id')
            ->first();
    }

    public function paginateForAdmin(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        // Canonical row per question = the earliest (MIN id) — the original LLM
        // answer of record; cache-hit replays share its (question_hash, locale).
        $canonicalIds = AiInquiry::query()
            ->selectRaw('MIN(id) as id')
            ->groupBy('question_hash', 'locale');

        return AiInquiry::query()
            ->select([
                'id', 'user_id', 'locale', 'question', 'answer', 'source', 'model',
                'input_tokens', 'output_tokens', 'cached_tokens', 'cost_usd', 'tool_calls',
                'geo', 'published_faq_id', 'answer_edited_at', 'created_at',
            ])
            ->whereIn('id', $canonicalIds)
            ->with(['user:id,name,email'])
            ->when(
                isset($filters['source']) && $filters['source'] !== '',
                fn ($query) => $query->where('source', $filters['source']),
            )
            ->when(
                isset($filters['published']) && $filters['published'] !== '',
                fn ($query) => (bool) $filters['published']
                    ? $query->whereNotNull('published_faq_id')
                    : $query->whereNull('published_faq_id'),
            )
            ->when(
                isset($filters['search']) && $filters['search'] !== '',
                fn ($query) => $query->where('question', 'like', '%'.$filters['search'].'%'),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function historyForUser(int $userId, int $limit = 30): Collection
    {
        // One entry per question for this user (latest), newest first.
        $latestIds = AiInquiry::query()
            ->selectRaw('MAX(id) as id')
            ->where('user_id', $userId)
            ->groupBy('question_hash', 'locale');

        return AiInquiry::query()
            ->select(['id', 'question', 'locale', 'created_at'])
            ->whereIn('id', $latestIds)
            ->where('user_id', $userId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function findForUser(int $id, int $userId): ?AiInquiry
    {
        return AiInquiry::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function purgeTrashedBefore(\DateTimeInterface $cutoff, bool $dryRun = false, int $chunk = 1000): int
    {
        return $this->purge(
            fn () => AiInquiry::onlyTrashed()->where('deleted_at', '<', $cutoff),
            $dryRun,
            $chunk,
        );
    }

    public function purgeCreatedBefore(\DateTimeInterface $cutoff, bool $dryRun = false, int $chunk = 1000): int
    {
        return $this->purge(
            fn () => AiInquiry::withTrashed()->where('created_at', '<', $cutoff),
            $dryRun,
            $chunk,
        );
    }

    /**
     * Batched hard-delete shared by the retention sweeps. Deletes by id in chunks
     * so a large backlog never holds a long table lock; with $dryRun it only counts.
     *
     * @param  \Closure(): Builder<AiInquiry>  $query
     */
    private function purge(\Closure $query, bool $dryRun, int $chunk): int
    {
        if ($dryRun) {
            return $query()->count();
        }

        $total = 0;
        do {
            $ids = $query()->limit($chunk)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $total += AiInquiry::withTrashed()->whereIn('id', $ids)->forceDelete();
        } while ($ids->count() === $chunk);

        return $total;
    }
}
