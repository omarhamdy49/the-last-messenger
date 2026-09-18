<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Language;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LanguageRepositoryInterface
{
    public function paginateAdmin(?string $search, int $perPage): LengthAwarePaginator;

    /**
     * @return Collection<int, Language>
     */
    public function active(): Collection;

    /**
     * @return array<string, string> iso => name, for selects
     */
    public function options(): array;

    public function find(int $id): ?Language;

    /**
     * @param  array{id?: int, name: string, native_name: ?string, iso_code: string, direction: string, is_active: bool}  $attributes
     */
    public function create(array $attributes): Language;

    /**
     * @param  array{name: string, native_name: ?string, iso_code: string, direction: string, is_active: bool}  $attributes
     */
    public function update(int $id, array $attributes): Language;

    public function toggle(int $id): Language;

    public function delete(int $id): void;
}
