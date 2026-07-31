<?php

declare(strict_types=1);

namespace App\Http\Resources\Hadith;

/**
 * Carries the response locale on the resource itself.
 *
 * The API resolves the locale from Accept-Language, but a JsonResource is
 * serialized outside the controller's frame and `app()->getLocale()` had already
 * been reset by then, so every response silently came back in English. Passing
 * the locale explicitly makes the resource independent of global state — and
 * testable without a request at all.
 */
trait ResolvesLocale
{
    protected ?string $locale = null;

    public function forLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    protected function locale(): string
    {
        return $this->locale ?? app()->getLocale();
    }
}
