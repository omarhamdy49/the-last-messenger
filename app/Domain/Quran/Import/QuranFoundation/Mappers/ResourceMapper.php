<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Mappers;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\ResourceSource;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use Illuminate\Support\Str;

/**
 * QF catalogue rows (`/resources/*`) → quran_resources rows.
 *
 * Only descriptive columns are produced. Editor-controlled columns (is_active,
 * is_default, sync_enabled, display_order, store_audio_locally) are never part of a
 * mapped row, so a catalogue re-import can never undo an editor's choice.
 */
final class ResourceMapper
{
    /**
     * Columns the catalogue import may overwrite on an existing row.
     *
     * @var list<string>
     */
    public const DESCRIPTIVE_COLUMNS = ['qf_content_id', 'name', 'author_name', 'language_iso', 'style', 'qirat', 'metadata'];

    /**
     * @param  array<string, mixed>  $qf
     * @param  array<string, string>  $languageIsoByName  lower-case QF language name => iso code
     * @return array<string, mixed>
     */
    public static function row(ResourceKind $kind, array $qf, array $languageIsoByName): array
    {
        $id = (int) ($qf['id'] ?? 0);
        if ($id <= 0) {
            throw new QfPayloadException((string) $kind->qfCataloguePath(), 'id');
        }

        $name = self::name($kind, $qf);
        $slug = trim((string) ($qf['slug'] ?? ''));

        $row = [
            'kind' => $kind->value,
            'qf_id' => $id,
            'qf_content_id' => isset($qf['resource_content_id']) ? (int) $qf['resource_content_id'] : $id,
            'source' => ResourceSource::QF->value,
            'slug' => $slug !== '' ? Str::limit(Str::slug($slug), 110, '') : $kind->value.'-'.$id,
            'name' => Str::limit($name, 200, ''),
            'author_name' => isset($qf['author_name']) ? Str::limit((string) $qf['author_name'], 200, '') : null,
            'language_iso' => self::languageIso($qf['language_name'] ?? null, $languageIsoByName),
            'style' => self::style($qf),
            'qirat' => isset($qf['qirat']['name']) ? Str::limit((string) $qf['qirat']['name'], 40, '') : null,
            'metadata' => json_encode(array_filter([
                'qf_slug' => $slug !== '' ? $slug : null,
                'qf_language_name' => $qf['language_name'] ?? null,
            ], static fn ($v): bool => $v !== null), JSON_UNESCAPED_UNICODE),
        ];

        // Reciter kinds can be fetched on demand from QF by the same id (admin
        // "Fetch data / audio / timestamps"), so record that source.
        if ($kind->isReciter()) {
            $row['external_source'] = ExternalSource::QF->value;
            $row['external_ref_id'] = (string) $id;
        }

        return $row;
    }

    /**
     * Name in the requested catalogue language, when QF actually translated it.
     *
     * @param  array<string, mixed>  $qf
     */
    public static function translatedName(array $qf, string $expectedLanguageName): ?string
    {
        $name = $qf['translated_name']['name'] ?? null;
        $language = strtolower((string) ($qf['translated_name']['language_name'] ?? ''));

        if (! is_string($name) || trim($name) === '' || $language !== strtolower($expectedLanguageName)) {
            return null;
        }

        return Str::limit(trim($name), 200, '');
    }

    /**
     * @param  array<string, mixed>  $qf
     */
    private static function name(ResourceKind $kind, array $qf): string
    {
        $name = match ($kind) {
            ResourceKind::RECITATION => $qf['reciter_name'] ?? null,
            default => $qf['name'] ?? null,
        };

        $name = is_string($name) ? trim($name) : '';

        return $name !== '' ? $name : $kind->value.' '.($qf['id'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $qf
     */
    private static function style(array $qf): ?string
    {
        $style = $qf['style'] ?? null;
        if (is_array($style)) {
            $style = $style['name'] ?? null;
        }

        return RecitationStyle::fromExternal(is_string($style) ? $style : null)?->value;
    }

    /**
     * @param  array<string, string>  $languageIsoByName
     */
    private static function languageIso(mixed $languageName, array $languageIsoByName): ?string
    {
        if (! is_string($languageName) || trim($languageName) === '') {
            return null;
        }

        return $languageIsoByName[strtolower(trim($languageName))] ?? null;
    }
}
