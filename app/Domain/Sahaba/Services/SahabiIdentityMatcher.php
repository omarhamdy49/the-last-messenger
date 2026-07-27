<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Services;

use App\Domain\Sahaba\Models\Sahabi;

/**
 * Resolves external narrator/companion names to one unambiguous Sahabi.
 *
 * It never performs fuzzy matching: a false identity merge is worse than an
 * unmatched source row. Arabic diacritics and punctuation are normalized.
 */
final class SahabiIdentityMatcher
{
    /**
     * @param  array<int, string|null>  $names
     */
    public function match(array $names): ?Sahabi
    {
        $needles = collect($names)
            ->map(fn (?string $name): string => self::normalize($name))
            ->filter()
            ->unique()
            ->values();

        if ($needles->isEmpty()) {
            return null;
        }

        $matches = Sahabi::query()
            ->where('is_sahabi', true)
            ->with('translations')
            ->get()
            ->filter(function (Sahabi $sahabi) use ($needles): bool {
                return $sahabi->translations->contains(function ($translation) use ($needles): bool {
                    foreach (['name', 'full_name', 'kunya', 'laqab'] as $field) {
                        if ($needles->contains(self::normalize($translation->{$field}))) {
                            return true;
                        }
                    }

                    return false;
                });
            })
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public static function normalize(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $name = mb_strtolower(trim($name));
        $name = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $name) ?? $name;
        $name = str_replace(['أ', 'إ', 'آ', 'ٱ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ا', 'ي', 'ه'], $name);
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }
}
