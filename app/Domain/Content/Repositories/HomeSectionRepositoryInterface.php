<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\DTOs\ReorderHomeSectionsData;
use App\Domain\Content\DTOs\UpdateHomeSectionData;
use App\Domain\Content\Models\HomeSection;
use Illuminate\Database\Eloquent\Collection;

interface HomeSectionRepositoryInterface
{
    /** @return Collection<int, HomeSection> */
    public function activeWithTranslations(): Collection;

    /** @return Collection<int, HomeSection> */
    public function allWithTranslations(): Collection;

    public function findWithTranslations(int $id): ?HomeSection;

    public function update(UpdateHomeSectionData $data): bool;

    public function setActive(int $id, bool $isActive): bool;

    public function reorder(ReorderHomeSectionsData $data): void;
}
