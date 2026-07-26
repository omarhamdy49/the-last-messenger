<?php

declare(strict_types=1);

namespace App\Domain\Shared\Helpers;

use Illuminate\Support\Facades\Cache;

final class VersionedCacheKey
{
    public static function bump(string $prefix): void
    {
        Cache::forever("{$prefix}:version", self::version($prefix) + 1);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function make(string $prefix, string $name, array $parameters = []): string
    {
        $key = "{$prefix}:v".self::version($prefix).":{$name}";

        return $parameters === [] ? $key : $key.':'.md5(serialize($parameters));
    }

    public static function version(string $prefix): int
    {
        return (int) Cache::get("{$prefix}:version", 0);
    }
}
