<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Mappers;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;

/**
 * Chapters, sections, pages and languages: QF payload rows → table rows.
 * JSON columns are returned already encoded (the importer writes with the query
 * builder, which does not cast).
 */
final class StructureMapper
{
    /**
     * @param  array<string, mixed>  $qf  one element of `/chapters`
     * @return array<string, mixed>
     */
    public static function chapter(array $qf): array
    {
        $pages = is_array($qf['pages'] ?? null) ? array_values($qf['pages']) : [];

        return [
            'id' => (int) self::required($qf, 'id', 'chapters'),
            'revelation_place' => RevelationPlace::fromExternal((string) ($qf['revelation_place'] ?? ''))->value,
            'revelation_order' => (int) ($qf['revelation_order'] ?? 0),
            'bismillah_pre' => (bool) ($qf['bismillah_pre'] ?? true),
            'name_simple' => (string) ($qf['name_simple'] ?? ''),
            'name_complex' => (string) ($qf['name_complex'] ?? $qf['name_simple'] ?? ''),
            'name_arabic' => (string) ($qf['name_arabic'] ?? ''),
            'verses_count' => (int) ($qf['verses_count'] ?? 0),
            'first_page' => (int) ($pages[0] ?? 1),
            'last_page' => (int) ($pages[1] ?? $pages[0] ?? 1),
        ];
    }

    /**
     * @param  array<string, mixed>  $qf
     */
    public static function chapterTranslatedName(array $qf): ?string
    {
        $name = $qf['translated_name']['name'] ?? null;

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    /**
     * QF returns more than one mapping set for some axes (e.g. 60 juz rows for 30
     * juz). Keep one row per number: the lowest QF id, which is the default set.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public static function sections(SectionType $type, array $items): array
    {
        $numberKey = $type->value.'_number';
        $byNumber = [];
        foreach ($items as $item) {
            $number = (int) self::required($item, $numberKey, $type->qfPath());
            if (! isset($byNumber[$number]) || (int) $item['id'] < (int) $byNumber[$number]['id']) {
                $byNumber[$number] = $item;
            }
        }
        ksort($byNumber);

        $rows = [];
        foreach ($byNumber as $number => $item) {
            $rows[] = [
                'type' => $type->value,
                'number' => $number,
                'surah_ruku_number' => isset($item['surah_ruku_number']) ? (int) $item['surah_ruku_number'] : null,
                'first_verse_id' => (int) ($item['first_verse_id'] ?? 0),
                'last_verse_id' => (int) ($item['last_verse_id'] ?? 0),
                'verses_count' => (int) ($item['verses_count'] ?? 0),
                'verse_mapping' => json_encode((object) ($item['verse_mapping'] ?? []), JSON_UNESCAPED_UNICODE),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $qf  one element of `/pages` or a mushaf_page record
     * @return array<string, mixed>
     */
    public static function page(int $mushafId, array $qf): array
    {
        return [
            'id' => (int) self::required($qf, 'id', 'pages'),
            'mushaf_id' => $mushafId,
            'page_number' => (int) self::required($qf, 'page_number', 'pages'),
            'first_verse_id' => (int) ($qf['first_verse_id'] ?? 0),
            'last_verse_id' => (int) ($qf['last_verse_id'] ?? 0),
            'first_word_id' => isset($qf['first_word_id']) ? (int) $qf['first_word_id'] : null,
            'last_word_id' => isset($qf['last_word_id']) ? (int) $qf['last_word_id'] : null,
            'verses_count' => (int) ($qf['verses_count'] ?? 0),
            'verse_mapping' => json_encode((object) ($qf['verse_mapping'] ?? []), JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * @param  array<string, mixed>  $qf  one element of `/resources/languages`
     * @return array<string, mixed>
     */
    public static function language(array $qf): array
    {
        return [
            'id' => (int) self::required($qf, 'id', 'resources/languages'),
            'name' => (string) ($qf['name'] ?? ''),
            'native_name' => isset($qf['native_name']) ? (string) $qf['native_name'] : null,
            'iso_code' => strtolower((string) self::required($qf, 'iso_code', 'resources/languages')),
            'direction' => ($qf['direction'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr',
            'translations_count' => (int) ($qf['translations_count'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function required(array $row, string $key, string $endpoint): mixed
    {
        if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            throw new QfPayloadException($endpoint, $key);
        }

        return $row[$key];
    }
}
