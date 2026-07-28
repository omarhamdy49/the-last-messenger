<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\DTOs\HomeSectionData;
use App\Domain\Content\DTOs\ReorderHomeSectionsData;
use App\Domain\Content\DTOs\UpdateHomeSectionData;
use App\Domain\Content\Models\HomeSection;
use App\Domain\Content\Repositories\HomeSectionRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final class HomeSectionService
{
    public function __construct(
        private readonly HomeSectionRepositoryInterface $repository,
    ) {}

    /** @return array<int, HomeSectionData> */
    public function active(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return Cache::rememberForever(
            ContentCacheKeys::homeSections($locale),
            fn (): array => $this->repository->activeWithTranslations()
                ->map(fn (HomeSection $section): HomeSectionData => $this->toData($section, $locale))
                ->all(),
        );
    }

    /** @return array<int, HomeSectionData> */
    public function forAdmin(): array
    {
        return $this->repository->allWithTranslations()
            ->map(fn (HomeSection $section): HomeSectionData => $this->toData($section, app()->getLocale()))
            ->all();
    }

    public function findForAdmin(int $id): ?HomeSectionData
    {
        $section = $this->repository->findWithTranslations($id);

        return $section === null ? null : $this->toData($section, app()->getLocale());
    }

    public function update(UpdateHomeSectionData $data): bool
    {
        $updated = $this->repository->update($data);

        if ($updated) {
            $this->flush();
        }

        return $updated;
    }

    public function setActive(int $id, bool $isActive): bool
    {
        $updated = $this->repository->setActive($id, $isActive);

        if ($updated) {
            $this->flush();
        }

        return $updated;
    }

    public function reorder(ReorderHomeSectionsData $data): void
    {
        $this->repository->reorder($data);
        $this->flush();
    }

    public function flush(): void
    {
        foreach (ContentCacheKeys::allHomeSectionKeys() as $key) {
            Cache::forget($key);
        }
    }

    private function toData(HomeSection $section, string $locale): HomeSectionData
    {
        return new HomeSectionData(
            id: $section->id,
            key: $section->key->value,
            isActive: $section->is_active,
            displayOrder: $section->display_order,
            heading: $section->getTranslation('heading', $locale),
            subheading: $section->getTranslation('subheading', $locale),
            headings: $section->getTranslations('heading'),
            subheadings: $section->getTranslations('subheading'),
        );
    }
}
