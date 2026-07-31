<?php

declare(strict_types=1);

namespace App\Http\Resources\Hadith;

use App\Domain\Hadith\Models\HadithChapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * contracts/hadith-api.md §5.
 *
 * @mixin HadithChapter
 */
class HadithChapterResource extends JsonResource
{
    use ResolvesLocale;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $locale = $this->locale();

        return array_filter([
            // Some chapters carry no number in the source; the field is omitted
            // rather than sent as an empty string.
            'chapter_number' => $this->chapter_number !== '' ? $this->chapter_number : null,
            'title' => $this->getTranslation('title', $locale, true) ?: null,
            'intro' => $this->getTranslation('intro', $locale, true) ?: null,
            'ending' => $this->getTranslation('ending', $locale, true) ?: null,
        ], static fn ($value): bool => $value !== null);
    }
}
