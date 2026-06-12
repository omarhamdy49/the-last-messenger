<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Models\StaticPage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the static pages repository.
 */
class StaticPageRepository implements StaticPageRepositoryInterface
{
    /**
     * @var array<int, string>
     */
    private const PAGE_COLUMNS = ['id', 'slug', 'is_active', 'published_at'];

    private const TRANSLATION_COLUMNS = 'translations:id,static_page_id,locale,title,body,seo_title,seo_description,is_ready';

    public function findPublishedBySlug(string $slug): ?StaticPage
    {
        return StaticPage::query()
            ->select(self::PAGE_COLUMNS)
            ->with([self::TRANSLATION_COLUMNS])
            ->published()
            ->where('slug', $slug)
            ->first();
    }

    public function allWithTranslations(): Collection
    {
        return StaticPage::query()
            ->select(self::PAGE_COLUMNS)
            ->with([self::TRANSLATION_COLUMNS])
            ->orderBy('slug')
            ->get();
    }

    public function findWithTranslations(int $id): ?StaticPage
    {
        return StaticPage::query()
            ->select(self::PAGE_COLUMNS)
            ->with([self::TRANSLATION_COLUMNS])
            ->find($id);
    }

    public function create(array $attributes, array $translations): StaticPage
    {
        return DB::transaction(function () use ($attributes, $translations): StaticPage {
            /** @var StaticPage $page */
            $page = StaticPage::query()->create($attributes);
            $page->syncTranslations($translations);

            return $page;
        });
    }

    public function update(int $id, array $attributes, array $translations): ?StaticPage
    {
        return DB::transaction(function () use ($id, $attributes, $translations): ?StaticPage {
            /** @var StaticPage|null $page */
            $page = StaticPage::query()->find($id);

            if ($page === null) {
                return null;
            }

            $page->update($attributes);
            $page->syncTranslations($translations);

            return $page;
        });
    }

    public function delete(int $id): bool
    {
        // Translations cascade via the static_page_id FK.
        return StaticPage::query()->whereKey($id)->delete() > 0;
    }
}
