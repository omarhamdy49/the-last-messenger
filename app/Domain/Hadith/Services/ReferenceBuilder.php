<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\DTOs\ReferenceSet;
use App\Domain\Hadith\Models\Hadith;

/**
 * Builds the scholarly reference lines from exact source values. A template is
 * only a fallback for the canonical line; optional citation lines are never
 * fabricated from structural numbers.
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
        $sourceReferences = $hadith->source_metadata['source']['references'] ?? [];

        return new ReferenceSet(
            canonical: $hadith->source_reference
                ?: $this->canonical($template, $hadith->hadith_number, $bookNumber, $code),
            inBook: $hadith->source_in_book_reference
                ?: $this->sourceValue($sourceReferences, [
                    'in_book_reference', 'In-book reference', 'book_reference', 'Book reference',
                    'arabic_english_book_reference', 'sunnah_com_reference',
                ]),
            translation: $hadith->source_translation_reference
                ?: $this->translationSourceValue($sourceReferences, $bookNumber),
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

    /**
     * Deliberately NOT localised. These are scholarly citations that a reader
     * copies into a paper or a message, and the spec requires them to match the
     * source character-for-character. The labels beside them are translated;
     * the citation itself is quoted, not translated.
     */
    /** @param mixed $references @param list<string> $keys */
    private function sourceValue(mixed $references, array $keys): ?string
    {
        if (! is_array($references)) {
            return null;
        }

        foreach ($keys as $key) {
            $value = $references[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function translationSourceValue(mixed $references, ?string $bookNumber): ?string
    {
        $reference = $this->sourceValue($references, [
            'english_translation', 'usc_msa_web_english_reference',
            'USC-MSA web (English) reference',
        ]);
        if ($reference === null || preg_match('/(?:Hadith|Narration)\s+0\b/iu', $reference) === 1) {
            return null;
        }
        if ($bookNumber !== null
            && preg_match('/Book\s+([0-9A-Za-z.-]+)/iu', $reference, $match) === 1
            && $match[1] !== $bookNumber) {
            return null;
        }

        return $reference;
    }
}
