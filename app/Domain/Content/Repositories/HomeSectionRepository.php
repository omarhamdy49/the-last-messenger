<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\DTOs\ReorderHomeSectionsData;
use App\Domain\Content\DTOs\UpdateHomeSectionData;
use App\Domain\Content\Models\HomeSection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class HomeSectionRepository implements HomeSectionRepositoryInterface
{
    private const COLUMNS = ['id', 'key', 'is_active', 'display_order'];

    private const TRANSLATIONS = 'translations:id,home_section_id,locale,heading,subheading,is_ready';

    public function activeWithTranslations(): Collection
    {
        return HomeSection::query()
            ->select(self::COLUMNS)
            ->with([self::TRANSLATIONS])
            ->active()
            ->ordered()
            ->get();
    }

    public function allWithTranslations(): Collection
    {
        return HomeSection::query()
            ->select(self::COLUMNS)
            ->with([self::TRANSLATIONS])
            ->ordered()
            ->get();
    }

    public function findWithTranslations(int $id): ?HomeSection
    {
        return HomeSection::query()
            ->select(self::COLUMNS)
            ->with([self::TRANSLATIONS])
            ->find($id);
    }

    public function update(UpdateHomeSectionData $data): bool
    {
        return DB::transaction(function () use ($data): bool {
            $section = HomeSection::query()->select(self::COLUMNS)->find($data->id);

            if ($section === null) {
                return false;
            }

            foreach (array_unique([...array_keys($data->headings), ...array_keys($data->subheadings)]) as $locale) {
                $heading = $data->headings[$locale] ?? null;
                $subheading = $data->subheadings[$locale] ?? null;

                $section->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'heading' => $heading,
                        'subheading' => $subheading,
                        'is_ready' => filled($heading) || filled($subheading),
                    ],
                );
            }

            return true;
        });
    }

    public function setActive(int $id, bool $isActive): bool
    {
        return DB::transaction(function () use ($id, $isActive): bool {
            $section = HomeSection::query()->select(['id', 'is_active'])->find($id);

            if ($section === null) {
                return false;
            }

            if ($section->is_active !== $isActive) {
                $section->update(['is_active' => $isActive]);
            }

            return true;
        });
    }

    public function reorder(ReorderHomeSectionsData $data): void
    {
        DB::transaction(function () use ($data): void {
            foreach ($data->displayOrderById as $id => $displayOrder) {
                HomeSection::query()->whereKey($id)->update(['display_order' => $displayOrder]);
            }
        });
    }
}
