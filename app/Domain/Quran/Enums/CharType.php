<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

use Illuminate\Support\Facades\Log;

/**
 * What a word row represents. Only `word` is a real word; the others are marks
 * rendered with the Unicode font (never a QCF glyph font), per the QF font guide.
 */
enum CharType: string
{
    case WORD = 'word';
    case END = 'end';
    case PAUSE = 'pause';
    case SAJDAH = 'sajdah';
    case RUB_EL_HIZB = 'rub_el_hizb';

    public static function fromExternal(?string $value): self
    {
        $normalized = str_replace('-', '_', strtolower(trim((string) $value)));

        $case = self::tryFrom($normalized);
        if ($case !== null) {
            return $case;
        }

        // An unknown mark must not abort an import of 83k words; record it once per
        // value so the discrepancy is visible (constitution X) and default to a word.
        static $logged = [];
        if (! isset($logged[$normalized])) {
            $logged[$normalized] = true;
            Log::warning('quran.import.unknown_char_type', ['value' => $normalized]);
        }

        return self::WORD;
    }

    public function isWord(): bool
    {
        return $this === self::WORD;
    }
}
