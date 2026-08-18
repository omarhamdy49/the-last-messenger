<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\Models\Faq;
use App\Domain\Content\Repositories\FaqRepositoryInterface;
use App\Domain\Shared\Contracts\FaqPublisherInterface;
use App\Domain\Shared\ValueObjects\FaqDraft;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * FAQ Service
 *
 * Serves active FAQs per locale, forever-cached (stable data), flushed on
 * writes. Category filtering happens on the cached list so one cache entry
 * per locale covers all category views.
 *
 * Implements the Shared FaqPublisherInterface so other domains can publish a
 * FAQ (e.g. Quran "Ask AI" moderation) without importing the Content domain.
 */
final class FaqService implements FaqPublisherInterface
{
    public function __construct(
        private readonly FaqRepositoryInterface $repository,
    ) {}

    /**
     * Active FAQs in display order, resolved for one locale and optionally
     * filtered by category.
     *
     * @return array<int, array{id: int, category: string|null, question: string|null, answer: string|null}>
     */
    public function active(?string $locale = null, ?string $category = null): array
    {
        $locale = $locale ?? app()->getLocale();

        $faqs = Cache::rememberForever(ContentCacheKeys::faqs($locale), function () use ($locale): array {
            return $this->repository->getActive()
                ->map(static fn (Faq $faq): array => [
                    'id' => $faq->id,
                    'category' => $faq->category,
                    'question' => $faq->getTranslation('question', $locale),
                    'answer' => $faq->getTranslation('answer', $locale),
                ])
                ->values()
                ->all();
        });

        if ($category === null) {
            return $faqs;
        }

        return array_values(array_filter(
            $faqs,
            static fn (array $faq): bool => $faq['category'] === $category
        ));
    }

    /**
     * Invalidate cached FAQ lists for every locale.
     */
    public function flush(): void
    {
        foreach (ContentCacheKeys::allFaqKeys() as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Every FAQ with translations for admin management (uncached).
     *
     * @return Collection<int, Faq>
     */
    public function faqsForAdmin(): Collection
    {
        return $this->repository->allWithTranslations();
    }

    /**
     * Find one FAQ with translations for the admin edit form (uncached).
     */
    public function faqForAdmin(int $id): ?Faq
    {
        return $this->repository->findWithTranslations($id);
    }

    /**
     * Create a FAQ and flush cached lists.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function create(array $attributes, array $translations): Faq
    {
        $faq = $this->repository->create($attributes, $translations);

        $this->flush();

        return $faq;
    }

    /**
     * Update a FAQ and flush cached lists.
     * Returns null when the FAQ does not exist.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function update(int $id, array $attributes, array $translations): ?Faq
    {
        $faq = $this->repository->update($id, $attributes, $translations);

        if ($faq !== null) {
            $this->flush();
        }

        return $faq;
    }

    /**
     * Delete a FAQ and flush cached lists.
     */
    public function delete(int $id): bool
    {
        $deleted = $this->repository->delete($id);

        if ($deleted) {
            $this->flush();
        }

        return $deleted;
    }

    /**
     * Publish a FAQ from a cross-domain draft (Shared FaqPublisherInterface).
     * Only the draft's source locale is filled; other languages are left blank
     * for an admin to complete in the FAQ editor.
     */
    public function publish(FaqDraft $draft): int
    {
        $faq = $this->create(
            ['category' => $draft->category, 'is_active' => $draft->isActive, 'sort_order' => null],
            [
                'question' => [$draft->locale => $draft->question],
                'answer' => [$draft->locale => $draft->answer],
            ],
        );

        return (int) $faq->id;
    }

    /**
     * Existing FAQ categories (Shared FaqPublisherInterface).
     *
     * @return list<string>
     */
    public function categories(): array
    {
        return $this->repository->distinctCategories();
    }
}
