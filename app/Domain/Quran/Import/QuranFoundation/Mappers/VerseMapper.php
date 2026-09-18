<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Mappers;

use App\Domain\Quran\Enums\CharType;
use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;

/**
 * Verses and words: QF payload rows → table rows.
 */
final class VerseMapper
{
    /**
     * Axis numbers and verse metadata from `/verses/by_chapter/{n}`.
     *
     * @param  array<string, mixed>  $qf
     * @return array<string, mixed>
     */
    public static function meta(array $qf): array
    {
        $id = (int) self::required($qf, 'id');
        $key = (string) self::required($qf, 'verse_key');
        [$chapter, $number] = array_map('intval', explode(':', $key) + [1 => 0]);

        $imageUrl = $qf['image_url'] ?? null;
        if (is_string($imageUrl) && str_starts_with($imageUrl, '//')) {
            $imageUrl = 'https:'.$imageUrl;
        }

        return [
            'id' => $id,
            'chapter_id' => (int) ($qf['chapter_id'] ?? $chapter),
            'verse_number' => (int) ($qf['verse_number'] ?? $number),
            'verse_key' => $key,
            // QF omits verse_index from by_chapter; the verse id is the mushaf index.
            'verse_index' => (int) ($qf['verse_index'] ?? $id),
            'juz_number' => (int) ($qf['juz_number'] ?? 0),
            'hizb_number' => (int) ($qf['hizb_number'] ?? 0),
            'rub_el_hizb_number' => (int) ($qf['rub_el_hizb_number'] ?? 0),
            'ruku_number' => (int) ($qf['ruku_number'] ?? 0),
            'manzil_number' => (int) ($qf['manzil_number'] ?? 0),
            'page_number' => (int) ($qf['page_number'] ?? 0),
            'sajdah_type' => isset($qf['sajdah_type']) ? (string) $qf['sajdah_type'] : null,
            'sajdah_number' => isset($qf['sajdah_number']) ? (int) $qf['sajdah_number'] : null,
            'image_url' => is_string($imageUrl) ? $imageUrl : null,
            'image_width' => isset($qf['image_width']) ? (int) $qf['image_width'] : null,
        ];
    }

    /**
     * The one script column (plus glyph page) carried by `/quran/verses/{script}`.
     *
     * @param  array<string, mixed>  $qf
     * @return array{id: int, verse_key: string, columns: array<string, mixed>}
     */
    public static function script(Script $script, array $qf): array
    {
        $columns = [$script->value => $qf[$script->value] ?? null];
        if ($script === Script::CODE_V1 && isset($qf['v1_page'])) {
            $columns['v1_page'] = (int) $qf['v1_page'];
        }
        if ($script === Script::CODE_V2 && isset($qf['v2_page'])) {
            $columns['v2_page'] = (int) $qf['v2_page'];
        }

        return [
            'id' => (int) self::required($qf, 'id'),
            'verse_key' => (string) self::required($qf, 'verse_key'),
            'columns' => $columns,
        ];
    }

    /**
     * @param  array<string, mixed>  $qf  one element of verse.words
     * @return array<string, mixed>
     */
    public static function word(array $qf, int $verseId): array
    {
        return [
            'id' => (int) self::required($qf, 'id'),
            'verse_id' => $verseId,
            'position' => (int) self::required($qf, 'position'),
            'location' => (string) self::required($qf, 'location'),
            'char_type' => CharType::fromExternal($qf['char_type_name'] ?? null)->value,
            'text_uthmani' => $qf['text_uthmani'] ?? null,
            'text_uthmani_simple' => $qf['text_uthmani_simple'] ?? null,
            'text_indopak' => $qf['text_indopak'] ?? null,
            'text_imlaei' => $qf['text_imlaei'] ?? null,
            'text_qpc_hafs' => $qf['text_qpc_hafs'] ?? null,
            'code_v1' => $qf['code_v1'] ?? null,
            'code_v2' => $qf['code_v2'] ?? null,
            'v1_page' => isset($qf['v1_page']) ? (int) $qf['v1_page'] : null,
            'v2_page' => isset($qf['v2_page']) ? (int) $qf['v2_page'] : null,
            'page_number' => (int) ($qf['page_number'] ?? 0),
            'line_number' => (int) ($qf['line_number'] ?? 0),
            'audio_url' => self::absoluteAudio($qf['audio_url'] ?? null),
        ];
    }

    /**
     * The word-by-word text a verse payload carries for `language`.
     *
     * @param  array<string, mixed>  $qf
     * @return array{translation: ?string, transliteration: ?string}
     */
    public static function glosses(array $qf): array
    {
        $clean = static function (mixed $value): ?string {
            return is_string($value) && trim($value) !== '' ? trim($value) : null;
        };

        return [
            'translation' => $clean($qf['translation']['text'] ?? null),
            'transliteration' => $clean($qf['transliteration']['text'] ?? null),
        ];
    }

    public static function absoluteAudio(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return rtrim((string) config('quran.qf.audio_base'), '/').'/'.ltrim($url, '/');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function required(array $row, string $key): mixed
    {
        if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            throw new QfPayloadException('verses', $key);
        }

        return $row[$key];
    }
}
