<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\DTOs\ReferenceSet;
use App\Domain\Hadith\Models\Hadith;

/**
 * Rebuilds the three scholarly reference lines from the collection template and
 * the stored numbers.
 *
 * They are rebuilt, never stored: storing them would let a corrected book number
 * and a stale reference line disagree, and the reference is the one thing a
 * reader cites elsewhere.
 *
 * Observed on sunnah.com 2026-07-30:
 *   bukhari  "Sahih al-Bukhari 1"                     + deprecated note
 *   tirmidhi "Jami` at-Tirmidhi 1"                    no note
 *   malik    "Book 1, Hadith 1"                       book-scoped canonical
 *   nawawi40 "Hadith 1, 40 Hadith an-Nawawi"          number mid-line
 *
 * The number is therefore substituted into a placeholder, never appended.
 */
final class ReferenceBuilder
{
    /**
     * sunnah.com prints "(deprecated numbering scheme)" only for these three.
     * Hardcoded rather than a column because it is a property of that site's
     * translation history, not of the collection itself.
     */
    private const DEPRECATED_TRANSLATION_NUMBERING = ['bukhari', 'muslim', 'malik'];

    public function build(Hadith $hadith): ReferenceSet
    {
        $code = (string) $hadith->collection->code;
        $template = $hadith->collection->reference_template;
        $bookNumber = $hadith->book?->book_number;

        return new ReferenceSet(
            canonical: $this->canonical($template, $hadith->hadith_number, $bookNumber, $code),
            inBook: $this->inBook($bookNumber, $hadith->in_book_number),
            translation: $this->translation($hadith->volume_number, $bookNumber, $hadith->in_book_number),
            translationDeprecated: in_array($code, self::DEPRECATED_TRANSLATION_NUMBERING, true),
        );
    }

    private function canonical(?string $template, string $hadithNumber, ?string $bookNumber, string $code): string
    {
        // Falling back to the code keeps the line honest when a collection has no
        // template yet: "bukhari 1" is obviously provisional, whereas an empty
        // string looks like a rendering bug.
        if ($template === null || $template === '') {
            return trim($code.' '.$hadithNumber);
        }

        return str_replace(
            ['{hadithNumber}', '{bookNumber}'],
            [$hadithNumber, $bookNumber ?? ''],
            $template,
        );
    }

    private function inBook(?string $bookNumber, ?int $inBookNumber): ?string
    {
        if ($bookNumber === null || $inBookNumber === null) {
            return null;
        }

        return __('hadith.reference_in_book', [
            'book' => $bookNumber,
            'hadith' => (string) $inBookNumber,
        ]);
    }

    /**
     * The volume segment appears only for collections whose translation is
     * volume-numbered; omitting it entirely is correct for the rest.
     */
    private function translation(?string $volumeNumber, ?string $bookNumber, ?int $inBookNumber): ?string
    {
        if ($bookNumber === null || $inBookNumber === null) {
            return null;
        }

        if ($volumeNumber === null || $volumeNumber === '') {
            return $this->inBook($bookNumber, $inBookNumber);
        }

        return __('hadith.reference_translation', [
            'volume' => $volumeNumber,
            'book' => $bookNumber,
            'hadith' => (string) $inBookNumber,
        ]);
    }
}
