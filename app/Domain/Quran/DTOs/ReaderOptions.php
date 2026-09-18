<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

use App\Domain\Quran\Enums\Script;

/**
 * What a reader/API request wants attached to verses. Resource ids are local
 * quran_resources ids that the service has already validated as active.
 */
final readonly class ReaderOptions
{
    /**
     * @param  list<int>  $translationIds
     * @param  list<int>  $tafsirIds
     */
    public function __construct(
        public Script $script = Script::UTHMANI,
        public array $translationIds = [],
        public array $tafsirIds = [],
        public bool $words = false,
        public ?string $wbwLanguage = null,
        public ?int $recitationId = null,
        public ?int $chapterReciterId = null,
        public ?int $mushafId = null,
        public bool $withTajweed = false,
    ) {}

    public function hash(): string
    {
        return substr(sha1(json_encode([
            $this->script->value, $this->translationIds, $this->tafsirIds, $this->words, $this->wbwLanguage,
            $this->recitationId, $this->chapterReciterId, $this->mushafId, $this->withTajweed,
        ]) ?: ''), 0, 16);
    }

    /**
     * @return list<string> verse columns to select for this request
     */
    public function scriptColumns(): array
    {
        $columns = [$this->script->value, 'text_uthmani', 'text_uthmani_simple'];
        if ($this->withTajweed) {
            $columns[] = 'text_uthmani_tajweed';
        }
        if ($this->script->isGlyph()) {
            $columns[] = $this->script === Script::CODE_V1 ? 'v1_page' : 'v2_page';
        }

        return array_values(array_unique($columns));
    }
}
