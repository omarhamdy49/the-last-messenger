<?php

declare(strict_types=1);

namespace App\Http\Resources\Hadith;

use App\Domain\Hadith\Models\HadithBook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * contracts/hadith-api.md §3 and §4. `intro` appears only on the detail
 * endpoint.
 *
 * @mixin HadithBook
 */
class HadithBookResource extends JsonResource
{
    use ResolvesLocale;

    public function __construct($resource, private readonly bool $withIntro = false)
    {
        parent::__construct($resource);
    }

    public static function detail(HadithBook $book): self
    {
        return new self($book, withIntro: true);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $locale = $this->locale();

        return array_filter([
            'book_number' => $this->book_number,
            'title' => $this->getTranslation('title', $locale, true) ?: null,
            'intro' => $this->withIntro ? ($this->getTranslation('intro', $locale, true) ?: null) : null,
            'first_hadith_number' => $this->first_hadith_number,
            'last_hadith_number' => $this->last_hadith_number,
            'hadith_count' => $this->hadiths_count !== null ? (int) $this->hadiths_count : null,
        ], static fn ($value): bool => $value !== null);
    }
}
