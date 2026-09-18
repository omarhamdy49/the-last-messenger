<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Language;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentLanguageRepository implements LanguageRepositoryInterface
{
    private const COLUMNS = ['id', 'name', 'native_name', 'iso_code', 'direction', 'translations_count', 'is_active'];

    public function paginateAdmin(?string $search, int $perPage): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return Language::query()
            ->select(self::COLUMNS)
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', $search.'%')
                ->orWhere('native_name', 'like', $search.'%')
                ->orWhere('iso_code', $search)))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(min(100, max(1, $perPage)))
            ->withQueryString();
    }

    public function active(): Collection
    {
        return Language::query()->select(self::COLUMNS)->active()->orderBy('name')->get();
    }

    public function options(): array
    {
        return Language::query()->select(['iso_code', 'name'])->orderBy('name')->pluck('name', 'iso_code')->all();
    }

    public function find(int $id): ?Language
    {
        return Language::query()->select(self::COLUMNS)->find($id);
    }

    public function create(array $attributes): Language
    {
        // Manual languages take ids above QF's range so a later QF import cannot collide.
        $attributes['id'] ??= max(10_000, ((int) DB::table('quran_languages')->max('id')) + 1);

        return Language::query()->create($attributes);
    }

    public function update(int $id, array $attributes): Language
    {
        $language = Language::query()->findOrFail($id);
        $language->update($attributes);

        return $language;
    }

    public function toggle(int $id): Language
    {
        $language = Language::query()->findOrFail($id);
        $language->update(['is_active' => ! $language->is_active]);

        return $language;
    }

    public function delete(int $id): void
    {
        Language::query()->whereKey($id)->delete();
    }
}
