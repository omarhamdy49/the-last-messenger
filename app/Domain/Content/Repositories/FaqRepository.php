<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the FAQ repository.
 */
class FaqRepository implements FaqRepositoryInterface
{
    public function getActive(): Collection
    {
        return Faq::query()
            ->select(['id', 'category', 'is_active', 'sort_order'])
            ->with(['translations:id,faq_id,locale,question,answer'])
            ->active()
            ->ordered()
            ->get();
    }

    public function allWithTranslations(): Collection
    {
        return Faq::query()
            ->select(['id', 'category', 'is_active', 'sort_order'])
            ->with(['translations:id,faq_id,locale,question,answer'])
            ->ordered()
            ->orderBy('id')
            ->get();
    }

    public function findWithTranslations(int $id): ?Faq
    {
        return Faq::query()
            ->select(['id', 'category', 'is_active', 'sort_order'])
            ->with(['translations:id,faq_id,locale,question,answer'])
            ->find($id);
    }

    public function create(array $attributes, array $translations): Faq
    {
        return DB::transaction(function () use ($attributes, $translations): Faq {
            if (! array_key_exists('sort_order', $attributes) || $attributes['sort_order'] === null) {
                $max = Faq::query()->max('sort_order');
                $attributes['sort_order'] = $max === null ? 0 : ((int) $max) + 1;
            }

            /** @var Faq $faq */
            $faq = Faq::query()->create($attributes);
            $faq->syncTranslations($translations);

            return $faq;
        });
    }

    public function update(int $id, array $attributes, array $translations): ?Faq
    {
        return DB::transaction(function () use ($id, $attributes, $translations): ?Faq {
            if (array_key_exists('sort_order', $attributes) && $attributes['sort_order'] === null) {
                // Keep the current position when the form sends no explicit order.
                unset($attributes['sort_order']);
            }

            /** @var Faq|null $faq */
            $faq = Faq::query()->find($id);

            if ($faq === null) {
                return null;
            }

            $faq->update($attributes);
            $faq->syncTranslations($translations);

            return $faq;
        });
    }

    public function delete(int $id): bool
    {
        // Translations cascade via the faq_id FK.
        return Faq::query()->whereKey($id)->delete() > 0;
    }

    public function distinctCategories(): array
    {
        return Faq::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }
}
