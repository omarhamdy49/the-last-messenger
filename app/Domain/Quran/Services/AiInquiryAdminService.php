<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\AiInquiry;
use App\Domain\Quran\Repositories\AiInquiryRepositoryInterface;
use App\Domain\Shared\Contracts\FaqPublisherInterface;
use App\Domain\Shared\ValueObjects\FaqDraft;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Admin moderation for "Ask Quran" AI inquiries: list, edit the stored answer,
 * delete, and publish an inquiry into the Content FAQ module.
 *
 * Cross-domain publishing goes through the Shared FaqPublisherInterface, so this
 * Quran service never imports the Content domain (keeps deptrac boundaries intact).
 */
final class AiInquiryAdminService
{
    public function __construct(
        private readonly AiInquiryRepositoryInterface $inquiries,
        private readonly FaqPublisherInterface $faqPublisher,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage, array $filters = []): LengthAwarePaginator
    {
        return $this->inquiries->paginateForAdmin($perPage, $filters);
    }

    public function find(int $id): ?AiInquiry
    {
        return $this->inquiries->findById($id);
    }

    /**
     * Existing FAQ categories (via the Shared publisher) for the publish picker.
     *
     * @return list<string>
     */
    public function faqCategories(): array
    {
        return $this->faqPublisher->categories();
    }

    /**
     * Edit the stored question and rich-text answer. If the question text
     * changes, its hash is recomputed; the cross-user answer cache is always
     * invalidated so the corrected answer is served going forward (never stale).
     */
    public function updateAnswer(int $id, string $question, string $answer, int $editorId): bool
    {
        $inquiry = $this->inquiries->findById($id);
        if ($inquiry === null) {
            return false;
        }

        $normalized = $this->normalize($question);
        $hash = hash('sha256', $normalized.'|'.$inquiry->locale);

        $updated = $this->inquiries->update($id, [
            'question' => mb_substr($question, 0, 255),
            'normalized_question' => mb_substr($normalized, 0, 255),
            'question_hash' => $hash,
            'answer' => $answer,
            'answer_edited_at' => Carbon::now(),
            'answer_edited_by' => $editorId,
        ]);

        if ($updated) {
            // Invalidate both the previous and the (possibly new) cache keys.
            Cache::forget(QuranCacheKeys::aiAnswer($inquiry->question_hash, $inquiry->locale));
            Cache::forget(QuranCacheKeys::aiAnswer($hash, $inquiry->locale));
        }

        return $updated;
    }

    /**
     * Mirror of the handler's question normalization (diacritics stripped,
     * lowercased, whitespace collapsed) so an edited question hashes consistently.
     */
    private function normalize(string $question): string
    {
        $stripped = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $question) ?? $question;
        $collapsed = preg_replace('/\s+/u', ' ', $stripped) ?? $stripped;

        return mb_strtolower(trim($collapsed));
    }

    public function delete(int $id): bool
    {
        return $this->inquiries->delete($id);
    }

    /**
     * Publish the (possibly edited) inquiry answer as a FAQ. Idempotent: if it
     * was already published, returns the existing FAQ id without creating a duplicate.
     *
     * @throws \RuntimeException when the inquiry does not exist.
     */
    public function publishAsFaq(int $id, ?string $category, bool $isActive): int
    {
        $inquiry = $this->inquiries->findById($id);
        if ($inquiry === null) {
            throw new \RuntimeException('ai_inquiry_not_found');
        }

        if ($inquiry->published_faq_id !== null) {
            return (int) $inquiry->published_faq_id;
        }

        $faqId = $this->faqPublisher->publish(new FaqDraft(
            question: $inquiry->question,
            answer: $inquiry->answer,
            locale: $inquiry->locale,
            category: $category,
            isActive: $isActive,
        ));

        $this->inquiries->update($id, ['published_faq_id' => $faqId]);

        return $faqId;
    }
}
