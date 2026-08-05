<?php

declare(strict_types=1);

namespace App\Http\Resources\Hadith;

use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * contracts/hadith-api.md §1 and §2. `about` appears only on the detail
 * endpoint, which is what `$withAbout` selects.
 *
 * @mixin HadithCollection
 */
class HadithCollectionResource extends JsonResource
{
    use ResolvesLocale;

    public function __construct($resource, private readonly bool $withAbout = false)
    {
        parent::__construct($resource);
    }

    public static function detail(HadithCollection $collection): self
    {
        return new self($collection, withAbout: true);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $locale = $this->locale();

        return array_filter([
            'code' => $this->code,
            'name' => $this->getTranslation('name', $locale, true) ?: null,
            'author' => $this->getTranslation('author', $locale, true) ?: null,
            'short_intro' => $this->getTranslation('short_intro', $locale, true) ?: null,
            'about' => $this->withAbout ? ($this->getTranslation('about', $locale, true) ?: null) : null,
            'introduction' => $this->withAbout ? ($this->getTranslation('introduction', $locale, true) ?: null) : null,
            'has_books' => $this->has_books,
            'has_chapters' => $this->has_chapters,
            'has_volumes' => $this->has_volumes,
            // Computed, never a stored counter.
            'total_hadiths' => $this->hadiths_count !== null ? (int) $this->hadiths_count : null,
            'featured' => $this->featured,
        ], static fn ($value): bool => $value !== null);
    }
}
