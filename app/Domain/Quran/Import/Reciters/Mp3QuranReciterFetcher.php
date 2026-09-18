<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\Reciters;

use App\Domain\Quran\DTOs\AudioFileData;
use App\Domain\Quran\DTOs\ReciterData;
use App\Domain\Quran\DTOs\ReciterPreview;
use App\Domain\Quran\DTOs\TimestampRowData;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\Support\Throttle;
use App\Domain\Quran\Repositories\ChapterRepositoryInterface;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * mp3quran.net (API v3). The reference id is a `moshaf` id: one recording of one
 * reciter, with one MP3 per chapter at `{server}/{chapter:03}.mp3`. Per-verse timings
 * come from `ayat_timing?surah=&read=`, where the read is matched by folder URL.
 */
final class Mp3QuranReciterFetcher implements ReciterFetcher
{
    /** @var array<string, array<string, mixed>> */
    private array $responses = [];

    /** @var array<int, int>|null chapter id => verses_count, read once per fetch */
    private ?array $versesCounts = null;

    private readonly Throttle $throttle;

    public function __construct(
        private readonly ChapterRepositoryInterface $chapters,
        ?Throttle $throttle = null,
    ) {
        $this->throttle = $throttle ?? new Throttle((int) config('quran.mp3quran.rate_limit_per_second', 2));
    }

    /**
     * Verses per chapter, read once per fetch. `reconcile()` runs for all 114 chapters,
     * so looking this up per chapter would be 114 queries inside the fetch loop.
     *
     * @return array<int, int>
     */
    private function versesCounts(): array
    {
        return $this->versesCounts ??= $this->chapters->all(publishedOnly: false)
            ->mapWithKeys(static fn ($chapter): array => [(int) $chapter->id => (int) $chapter->verses_count])
            ->all();
    }

    public function lookup(string $refId, ResourceKind $kind): ReciterPreview
    {
        [$reciter, $moshaf] = $this->moshaf($refId, $kind, 'ar');
        $chapters = $this->chapters($moshaf);

        return new ReciterPreview(
            name: trim((string) $reciter['name']),
            style: RecitationStyle::fromExternal((string) ($moshaf['name'] ?? '')),
            qirat: $this->qirat((string) ($moshaf['name'] ?? '')),
            filesCount: count($chapters),
            hasTimestamps: $this->readId($moshaf) !== null,
            previewUrls: array_map(fn (int $c): string => $this->fileUrl($moshaf, $c), array_slice($chapters, 0, 3)),
        );
    }

    public function data(string $refId, ResourceKind $kind): ReciterData
    {
        [$reciter, $moshaf] = $this->moshaf($refId, $kind, 'ar');
        $names = ['ar' => trim((string) $reciter['name'])];

        try {
            [$english] = $this->moshaf($refId, $kind, 'eng');
            $names['en'] = trim((string) $english['name']);
        } catch (InvalidArgumentException) {
            // English list lags behind sometimes; Arabic is enough.
        }

        return new ReciterData(
            name: $names['en'] ?? $names['ar'],
            names: $names,
            style: RecitationStyle::fromExternal((string) ($moshaf['name'] ?? '')),
            qirat: $this->qirat((string) ($moshaf['name'] ?? '')),
        );
    }

    public function files(string $refId, ResourceKind $kind): iterable
    {
        [, $moshaf] = $this->moshaf($refId, $kind, 'ar');

        foreach ($this->chapters($moshaf) as $chapter) {
            yield new AudioFileData(target: $chapter, url: $this->fileUrl($moshaf, $chapter));
        }
    }

    public function timestamps(string $refId, int $chapter): array
    {
        [, $moshaf] = $this->moshaf($refId, ResourceKind::CHAPTER_RECITER, 'ar');
        $readId = $this->readId($moshaf);
        if ($readId === null) {
            return [];
        }

        $rows = [];
        foreach ($this->get('ayat_timing', ['surah' => $chapter, 'read' => $readId]) as $row) {
            if (is_array($row) && isset($row['ayah'], $row['start_time'], $row['end_time'])) {
                $rows[] = [(int) $row['ayah'], (int) $row['start_time'], (int) $row['end_time']];
            }
        }
        usort($rows, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return array_map(
            static fn (array $r): TimestampRowData => new TimestampRowData("{$chapter}:{$r[0]}", $r[1], $r[2]),
            $this->reconcile($rows, $chapter, $readId),
        );
    }

    /**
     * mp3quran numbers ayat in more than one way. Most reads add an ayah-0 bismillah row
     * before real ayat; some have none; a few (Husary's al-Fatiha) count 0..n-1, one short.
     * Anything that cannot be explained is refused: no highlighting beats highlighting
     * the wrong verse of the Quran.
     *
     * @param  list<array{int, int, int}>  $rows  sorted by ayah
     * @return list<array{int, int, int}>
     */
    private function reconcile(array $rows, int $chapter, int $readId): array
    {
        $expected = $this->versesCounts()[$chapter] ?? 0;
        $where = "mp3quran ayat_timing surah={$chapter} read={$readId}";
        if ($rows === [] || $expected < 1) {
            return [];
        }

        $indices = array_column($rows, 0);
        if (count(array_unique($indices)) !== count($indices)) {
            throw new RuntimeException("{$where}: duplicate ayah indices");
        }
        foreach ($rows as $i => [, $start, $end]) {
            if ($end < $start || ($i > 0 && $start < $rows[$i - 1][1])) {
                throw new RuntimeException("{$where}: times are not monotonic");
            }
        }

        $max = max($indices);
        if ($max === $expected - 1 && $indices[0] === 0) {
            $rows = array_map(static fn (array $r): array => [$r[0] + 1, $r[1], $r[2]], $rows);
        } elseif ($max !== $expected) {
            throw new RuntimeException("{$where}: max ayah {$max}, expected {$expected}");
        }

        return array_values(array_filter($rows, static fn (array $r): bool => $r[0] >= 1));
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function moshaf(string $refId, ResourceKind $kind, string $language): array
    {
        if ($kind !== ResourceKind::CHAPTER_RECITER) {
            throw new InvalidArgumentException('mp3quran recordings are whole-chapter files; use a chapter reciter.');
        }
        if (! ctype_digit($refId)) {
            throw new InvalidArgumentException('An mp3quran moshaf id must be a number.');
        }

        foreach ((array) ($this->get('reciters', ['language' => $language])['reciters'] ?? []) as $reciter) {
            foreach ((array) ($reciter['moshaf'] ?? []) as $moshaf) {
                if ((int) ($moshaf['id'] ?? 0) === (int) $refId) {
                    return [$reciter, $moshaf];
                }
            }
        }

        throw new InvalidArgumentException("mp3quran has no moshaf with id {$refId}.");
    }

    /**
     * @param  array<string, mixed>  $moshaf
     * @return list<int>
     */
    private function chapters(array $moshaf): array
    {
        $list = array_map('intval', array_filter(explode(',', (string) ($moshaf['surah_list'] ?? ''))));

        return array_values(array_unique(array_filter($list, static fn (int $c): bool => $c >= 1 && $c <= 114)));
    }

    /**
     * @param  array<string, mixed>  $moshaf
     */
    private function fileUrl(array $moshaf, int $chapter): string
    {
        return rtrim((string) $moshaf['server'], '/').'/'.sprintf('%03d', $chapter).'.mp3';
    }

    /**
     * @param  array<string, mixed>  $moshaf
     */
    private function readId(array $moshaf): ?int
    {
        $folder = rtrim((string) ($moshaf['server'] ?? ''), '/');
        foreach ($this->get('ayat_timing/reads', []) as $read) {
            if (is_array($read) && rtrim((string) ($read['folder_url'] ?? ''), '/') === $folder) {
                return (int) $read['id'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<int|string, mixed>
     */
    private function get(string $path, array $query): array
    {
        $key = $path.'?'.http_build_query($query);
        if (isset($this->responses[$key])) {
            return $this->responses[$key];
        }

        $url = rtrim((string) config('quran.mp3quran.api_url'), '/').'/'.$path;
        $maxRetries = (int) config('quran.qf.max_retries', 3);

        for ($attempt = 0; ; $attempt++) {
            $this->throttle->wait();
            $response = Http::timeout((int) config('quran.mp3quran.timeout', 30))->acceptJson()->get($url, $query);

            if (($response->status() === 429 || $response->serverError()) && $attempt < $maxRetries) {
                $this->throttle->backoff($attempt + 1, $response->header('Retry-After') ?: null);

                continue;
            }
            if (! $response->successful() || ! is_array($response->json())) {
                throw new RuntimeException("mp3quran {$path} returned HTTP {$response->status()}");
            }

            return $this->responses[$key] = $response->json();
        }
    }

    private function qirat(string $moshafName): ?string
    {
        foreach (['حفص' => 'Hafs', 'ورش' => 'Warsh', 'قالون' => 'Qalun', 'الدوري' => 'Ad-Duri', 'السوسي' => 'As-Susi', 'شعبة' => "Shu'bah", 'البزي' => 'Al-Bazzi', 'قنبل' => 'Qunbul', 'خلف' => 'Khalaf', 'هشام' => 'Hisham', 'ابن ذكوان' => 'Ibn Dhakwan'] as $arabic => $name) {
            if (str_contains($moshafName, $arabic)) {
                return $name;
            }
        }

        return null;
    }
}
