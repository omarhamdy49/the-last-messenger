<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

/**
 * The QF "verse params" of the public API, already validated.
 */
final readonly class VerseApiParams
{
    /**
     * @param  list<int>  $translationIds
     * @param  list<int>  $tafsirIds
     * @param  list<string>  $fields  extra verse columns
     * @param  list<string>  $wordFields  extra word columns
     */
    public function __construct(
        public ?string $language = null,
        public bool $words = false,
        public array $translationIds = [],
        public array $tafsirIds = [],
        public ?int $audioId = null,
        public array $fields = [],
        public array $wordFields = [],
        public int $page = 1,
        public int $perPage = 10,
    ) {}
}
