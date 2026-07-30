<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * The single publication gate for the whole module.
 *
 * Every public read routes its `is_published` decision through here so the rule
 * cannot drift between the web pages, the API and the search index. `grep -rn
 * "is_published" app/Domain/Hadith` should match only this class, the models'
 * $fillable/$casts, and the repositories' explicit read scopes.
 *
 * Locale plays no part: Arabic is always present and is the fallback for every
 * other locale, so a hadith is never hidden for lacking a translation. That is
 * the opposite of Seerah, where an unready translation hides the row.
 */
final class HadithVisibility implements PublicVisibilityProviderInterface
{
    public function modelClasses(): array
    {
        return [Hadith::class, HadithCollection::class, HadithBook::class, HadithChapter::class];
    }

    public function apply(Builder $query, string $locale): Builder
    {
        $query->where($query->getModel()->getTable().'.is_published', true);

        // A synthetic book exists only so bookless collections satisfy the
        // hierarchy's NOT NULL book_id. It must never surface in a listing or a
        // count, or the three forties appear to contain a book called "0".
        if ($query->getModel() instanceof HadithBook) {
            $query->where('is_synthetic', false);
        }

        return $query;
    }

    /**
     * Whether a loaded hadith may be shown. Used by the detail path, which has
     * already fetched the row and must decide 200 versus 404 without re-querying.
     */
    public function allowsHadith(Hadith $hadith): bool
    {
        return $hadith->is_published
            && (bool) $hadith->collection?->is_published;
    }
}
