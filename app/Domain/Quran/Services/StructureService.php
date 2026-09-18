<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Models\Language;
use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\MushafPage;
use App\Domain\Quran\Models\Section;
use App\Domain\Quran\Repositories\LanguageRepositoryInterface;
use App\Domain\Quran\Repositories\MushafRepositoryInterface;
use App\Domain\Quran\Repositories\SectionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Sections, mushafs and content languages — the structural reference data.
 */
final class StructureService
{
    public function __construct(
        private readonly SectionRepositoryInterface $sections,
        private readonly MushafRepositoryInterface $mushafs,
        private readonly LanguageRepositoryInterface $languages,
    ) {}

    /**
     * @return Collection<int, Section>
     */
    public function sections(SectionType $type): Collection
    {
        return $this->sections->ofType($type);
    }

    public function section(SectionType $type, int $number): ?Section
    {
        return $this->sections->find($type, $number);
    }

    /**
     * @return array<string, int>
     */
    public function sectionCounts(): array
    {
        return $this->sections->counts();
    }

    /**
     * @return Collection<int, Mushaf>
     */
    public function mushafs(bool $activeOnly = false): Collection
    {
        return $activeOnly ? $this->mushafs->active() : $this->mushafs->all();
    }

    public function mushaf(int $id): ?Mushaf
    {
        return $this->mushafs->find($id);
    }

    public function defaultMushaf(): ?Mushaf
    {
        return $this->mushafs->default();
    }

    /**
     * @return array{page: ?MushafPage, lines: array<int, list<array<string, mixed>>>}
     */
    public function mushafPage(int $mushafId, int $pageNumber): array
    {
        return ['page' => $this->mushafs->page($mushafId, $pageNumber), 'lines' => $this->mushafs->pageLines($mushafId, $pageNumber)];
    }

    public function pageOfVerse(int $mushafId, int $verseId): ?int
    {
        return $this->mushafs->pageOfVerse($mushafId, $verseId);
    }

    /**
     * @param  array{is_active?: bool, is_default?: bool, name?: string}  $attributes
     */
    public function updateMushaf(int $id, array $attributes): Mushaf
    {
        $mushaf = $this->mushafs->update($id, $attributes);
        QuranCacheKeys::flushMushafs();
        QuranCacheKeys::flushContent();

        return $mushaf;
    }

    public function languagesForAdmin(?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->languages->paginateAdmin($search, $perPage);
    }

    /**
     * @return array<string, string>
     */
    public function languageOptions(): array
    {
        return $this->languages->options();
    }

    public function language(int $id): ?Language
    {
        return $this->languages->find($id);
    }

    /**
     * @param  array{name: string, native_name: ?string, iso_code: string, direction: string, is_active: bool}  $attributes
     */
    public function createLanguage(array $attributes): Language
    {
        return $this->languages->create($attributes);
    }

    /**
     * @param  array{name: string, native_name: ?string, iso_code: string, direction: string, is_active: bool}  $attributes
     */
    public function updateLanguage(int $id, array $attributes): Language
    {
        return $this->languages->update($id, $attributes);
    }

    public function toggleLanguage(int $id): Language
    {
        return $this->languages->toggle($id);
    }

    /**
     * Only manually added languages (ids ≥ 10000) are deleted; QF languages come back
     * on the next structure import, so they are deactivated instead.
     */
    public function deleteLanguage(int $id): void
    {
        if ($id < 10_000) {
            throw new \InvalidArgumentException('Quran Foundation languages can only be deactivated.');
        }

        $this->languages->delete($id);
    }
}
