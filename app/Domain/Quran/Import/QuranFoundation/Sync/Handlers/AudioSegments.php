<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync\Handlers;

/**
 * Word timing segments come in two shapes: chapter timestamps use
 * [word, start_ms, end_ms]; ayah audio files use [index, word, start_ms, end_ms].
 * Both are stored as [word, start_ms, end_ms].
 */
final class AudioSegments
{
    /**
     * @return list<array{0: int, 1: int, 2: int}>|null
     */
    public static function normalize(mixed $segments): ?array
    {
        if (! is_array($segments) || $segments === []) {
            return null;
        }

        $normalized = [];
        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }
            $values = array_values(array_map('intval', $segment));
            if (count($values) === 4) {
                array_shift($values);
            }
            if (count($values) === 3) {
                $normalized[] = [$values[0], $values[1], $values[2]];
            }
        }

        return $normalized === [] ? null : $normalized;
    }
}
