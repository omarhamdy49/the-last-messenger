<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\Reciters;

use App\Domain\Quran\DTOs\AudioFileData;
use App\Domain\Quran\DTOs\ReciterData;
use App\Domain\Quran\DTOs\ReciterPreview;
use App\Domain\Quran\DTOs\TimestampRowData;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\QuranFoundation\Mappers\ResourceMapper;
use App\Domain\Quran\Import\QuranFoundation\Mappers\VerseMapper;
use App\Domain\Quran\Import\QuranFoundation\Sync\Handlers\AudioSegments;
use InvalidArgumentException;

/**
 * Quran Foundation reciters: `chapter_reciters` (one file per chapter, with verse
 * timestamps) and `recitations` (one file per verse, with word segments).
 */
final class QfReciterFetcher implements ReciterFetcher
{
    public function __construct(private readonly Endpoints $qf) {}

    public function lookup(string $refId, ResourceKind $kind): ReciterPreview
    {
        $item = $this->catalogueItem($refId, $kind, 'en');
        $id = (int) $refId;

        if ($kind === ResourceKind::CHAPTER_RECITER) {
            $files = (array) ($this->qf->chapterRecitations($id)['audio_files'] ?? []);
            $hasTimestamps = $this->chapterTimestampsExist($id);
            $urls = array_map(static fn (array $f): string => (string) VerseMapper::absoluteAudio($f['audio_url'] ?? null), array_slice($files, 0, 3));
        } else {
            $files = (array) ($this->qf->recitationFiles($id, ['chapter_number' => 1])['audio_files'] ?? []);
            $hasTimestamps = isset($files[0]['segments']) && $files[0]['segments'] !== [];
            $urls = array_map(static fn (array $f): string => (string) VerseMapper::absoluteAudio($f['url'] ?? null), array_slice($files, 0, 3));
        }

        return new ReciterPreview(
            name: $this->name($item, $kind),
            style: $this->style($item),
            qirat: isset($item['qirat']['name']) ? (string) $item['qirat']['name'] : null,
            filesCount: count($files),
            hasTimestamps: $hasTimestamps,
            previewUrls: array_values(array_filter($urls)),
        );
    }

    public function data(string $refId, ResourceKind $kind): ReciterData
    {
        $item = $this->catalogueItem($refId, $kind, 'en');
        $names = ['en' => $this->name($item, $kind)];

        foreach (['ar' => 'arabic', 'fr' => 'french', 'tr' => 'turkish', 'ur' => 'urdu'] as $locale => $languageName) {
            $localized = $this->catalogueItem($refId, $kind, $locale);
            $name = ResourceMapper::translatedName($localized, $languageName);
            if ($name !== null) {
                $names[$locale] = $name;
            }
        }

        $info = null;
        if ($kind->hasQfInfo()) {
            try {
                $info = $this->qf->resourceInfo($kind, (int) $refId)['info']['info'] ?? null;
            } catch (QfApiException $e) {
                if ($e->status !== 404) {
                    throw $e;
                }
            }
        }

        return new ReciterData($names['en'], $names, $this->style($item), isset($item['qirat']['name']) ? (string) $item['qirat']['name'] : null,
            is_string($info) && trim($info) !== '' ? $info : null);
    }

    public function files(string $refId, ResourceKind $kind): iterable
    {
        $id = (int) $refId;

        if ($kind === ResourceKind::CHAPTER_RECITER) {
            foreach ((array) ($this->qf->chapterRecitations($id)['audio_files'] ?? []) as $file) {
                $url = VerseMapper::absoluteAudio($file['audio_url'] ?? null);
                if ($url !== null && isset($file['chapter_id'])) {
                    yield new AudioFileData(target: (int) $file['chapter_id'], url: $url, format: (string) ($file['format'] ?? 'mp3'),
                        fileSize: isset($file['file_size']) ? (int) $file['file_size'] : null);
                }
            }

            return;
        }

        for ($chapter = 1; $chapter <= 114; $chapter++) {
            foreach ((array) ($this->qf->recitationFiles($id, ['chapter_number' => $chapter])['audio_files'] ?? []) as $file) {
                $url = VerseMapper::absoluteAudio($file['url'] ?? null);
                if ($url !== null && isset($file['verse_key'])) {
                    yield new AudioFileData(target: (string) $file['verse_key'], url: $url, format: (string) ($file['format'] ?? 'mp3'),
                        durationMs: isset($file['duration']) ? (int) round((float) $file['duration'] * 1000) : null,
                        segments: AudioSegments::normalize($file['segments'] ?? null));
                }
            }
        }
    }

    public function timestamps(string $refId, int $chapter): array
    {
        try {
            $file = $this->qf->chapterRecitation((int) $refId, $chapter, true)['audio_file'] ?? [];
        } catch (QfApiException $e) {
            if ($e->status === 404) {
                return [];
            }
            throw $e;
        }

        $rows = [];
        foreach ((array) ($file['timestamps'] ?? []) as $stamp) {
            if (isset($stamp['verse_key'], $stamp['timestamp_from'], $stamp['timestamp_to'])) {
                $rows[] = new TimestampRowData((string) $stamp['verse_key'], (int) $stamp['timestamp_from'], (int) $stamp['timestamp_to'],
                    AudioSegments::normalize($stamp['segments'] ?? null));
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogueItem(string $refId, ResourceKind $kind, string $language): array
    {
        if (! $kind->isReciter() || ! ctype_digit($refId)) {
            throw new InvalidArgumentException("Quran Foundation {$kind->value} id must be a number.");
        }

        foreach ((array) ($this->qf->catalogue($kind, $language)[(string) $kind->qfCatalogueKey()] ?? []) as $item) {
            if ((int) ($item['id'] ?? 0) === (int) $refId) {
                return $item;
            }
        }

        throw new InvalidArgumentException("Quran Foundation has no {$kind->value} with id {$refId}.");
    }

    private function chapterTimestampsExist(int $id): bool
    {
        try {
            return ((array) ($this->qf->chapterRecitation($id, 1, true)['audio_file']['timestamps'] ?? [])) !== [];
        } catch (QfApiException) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function name(array $item, ResourceKind $kind): string
    {
        return trim((string) ($kind === ResourceKind::RECITATION ? ($item['reciter_name'] ?? '') : ($item['name'] ?? '')));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function style(array $item): ?RecitationStyle
    {
        $style = $item['style'] ?? null;

        return RecitationStyle::fromExternal(is_array($style) ? (string) ($style['name'] ?? '') : (string) $style);
    }
}
