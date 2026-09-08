<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Mp3Quran;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * The mp3quran.net catalogue, fetched once and indexed for lookup.
 *
 * Two endpoints, because neither is enough on its own: the reciter list says
 * what audio exists and where, and the ayat-timing list says which of those
 * recordings has per-ayah timings (what Phase 2 needs for highlighting).
 * A recitation's `read` id and its `moshaf` id are the same number, so the
 * folder URL is all that connects the two.
 *
 * Fetching is all-or-nothing on purpose: a half-loaded catalogue would look
 * like reciters who lost their audio, and callers write from it.
 */
final class Mp3QuranCatalogue
{
    private const RECITERS = 'https://www.mp3quran.net/api/v3/reciters?language=';

    private const TIMED_READS = 'https://www.mp3quran.net/api/v3/ayat_timing/reads';

    /**
     * @param  list<array{id: int, name: string}>  $reciters
     * @param  array<int, list<array<string, mixed>>>  $moshafsByReciter
     * @param  array<int, array<string, mixed>>  $moshafsById
     * @param  array<string, int>  $readsByFolder
     * @param  array<int, string>  $englishNames
     * @param  array<int, string>  $englishMoshafNames
     */
    private function __construct(
        private readonly array $reciters,
        private readonly array $moshafsByReciter,
        private readonly array $moshafsById,
        private readonly array $readsByFolder,
        private readonly array $englishNames,
        private readonly array $englishMoshafNames,
    ) {}

    /**
     * @throws RuntimeException when either endpoint is unusable
     */
    public static function fetch(): self
    {
        $catalogue = self::get(self::RECITERS.'ar', 'reciters');
        $reads = self::get(self::TIMED_READS);

        // Arabic is authoritative for structure; English is only read for
        // names, so that a reciter we create is not Arabic in both columns.
        [$englishNames, $englishMoshafNames] = self::englishIndex();

        $readsByFolder = [];

        foreach ($reads as $read) {
            $readsByFolder[self::folderKey((string) ($read['folder_url'] ?? ''))] = (int) ($read['id'] ?? 0);
        }

        $reciters = [];
        $byReciter = [];
        $byId = [];

        foreach ($catalogue as $entry) {
            $reciterId = (int) ($entry['id'] ?? 0);
            $name = (string) ($entry['name'] ?? '');

            if ($reciterId === 0 || $name === '') {
                continue;
            }

            $reciters[] = ['id' => $reciterId, 'name' => $name];

            foreach ((array) ($entry['moshaf'] ?? []) as $moshaf) {
                $row = self::normalizeMoshaf($moshaf, $reciterId, $name);

                if ($row === null) {
                    continue;
                }

                $byReciter[$reciterId][] = $row;
                $byId[$row['id']] = $row;
            }
        }

        if ($reciters === []) {
            throw new RuntimeException('mp3quran.net returned no reciters — aborting without writing anything.');
        }

        return new self($reciters, $byReciter, $byId, $readsByFolder, $englishNames, $englishMoshafNames);
    }

    /**
     * Every reciter in the catalogue, for name matching.
     *
     * @return list<array{id: int, name: string}>
     */
    public function reciters(): array
    {
        return $this->reciters;
    }

    /**
     * The recordings one catalogue reciter has: a qari may have a Hafs
     * murattal, a Warsh, a mujawwad and a mu'allim mushaf, and they are
     * different audio.
     *
     * @return list<array<string, mixed>>
     */
    public function moshafsFor(int $reciterId): array
    {
        return $this->moshafsByReciter[$reciterId] ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function moshaf(int $moshafId): ?array
    {
        return $this->moshafsById[$moshafId] ?? null;
    }

    /**
     * Every recording in the catalogue, for the import-everything path.
     *
     * @return array<int, array<string, mixed>>
     */
    public function moshafs(): array
    {
        return $this->moshafsById;
    }

    public function englishName(int $reciterId): ?string
    {
        return $this->englishNames[$reciterId] ?? null;
    }

    public function englishMoshafName(int $moshafId): ?string
    {
        return $this->englishMoshafNames[$moshafId] ?? null;
    }

    /** The ayat-timing read id for a recording, or null when it has no timings. */
    public function readIdFor(string $folder): ?int
    {
        return $this->readsByFolder[self::folderKey($folder)] ?? null;
    }

    /**
     * Whole-surah audio URLs for a recording, keyed by surah number.
     *
     * The file name is the surah zero-padded to three digits: /basit/001.mp3
     * is the Fatiha and /basit/1.mp3 is a 404.
     *
     * @param  array<string, mixed>  $moshaf
     * @return array<int, string>
     */
    public static function audioUrls(array $moshaf): array
    {
        $urls = [];

        foreach ($moshaf['surahs'] as $surahId) {
            $urls[$surahId] = $moshaf['server'].sprintf('%03d.mp3', $surahId);
        }

        return $urls;
    }

    /**
     * @param  array<string, mixed>  $moshaf
     * @return array<string, mixed>|null null when the entry has no usable audio
     */
    private static function normalizeMoshaf(array $moshaf, int $reciterId, string $reciterName): ?array
    {
        $server = (string) ($moshaf['server'] ?? '');

        $surahs = array_values(array_filter(
            array_map('intval', explode(',', (string) ($moshaf['surah_list'] ?? ''))),
            static fn (int $surah) => $surah >= 1 && $surah <= 114,
        ));

        if ($server === '' || $surahs === []) {
            return null;
        }

        return [
            'id' => (int) ($moshaf['id'] ?? 0),
            'reciter_id' => $reciterId,
            'reciter' => $reciterName,
            'name' => (string) ($moshaf['name'] ?? ''),
            'rewaya_id' => (int) ($moshaf['rewaya_id'] ?? 0),
            'server' => str_ends_with($server, '/') ? $server : $server.'/',
            'surahs' => $surahs,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws RuntimeException
     */
    private static function get(string $url, ?string $key = null): array
    {
        try {
            // Retries cover a dropped connection; an HTTP error is reported as
            // it stands, because retrying a 404 only wastes the operator's time.
            $response = Http::timeout(30)->retry(3, 1000, throw: false)->get($url);
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException("Could not reach {$url}: {$e->getMessage()}", previous: $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException("Could not reach {$url}: HTTP {$response->status()}");
        }

        $data = $key === null ? $response->json() : $response->json($key);

        if (! is_array($data) || $data === []) {
            throw new RuntimeException("Unexpected response from {$url} — aborting without writing anything.");
        }

        return $data;
    }

    /**
     * English names keyed by reciter id and by moshaf id.
     *
     * A missing or broken English list is not worth aborting a run over — the
     * Arabic name alone still makes a usable reciter — so this one degrades to
     * empty instead of throwing.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private static function englishIndex(): array
    {
        try {
            $english = self::get(self::RECITERS.'eng', 'reciters');
        } catch (RuntimeException $e) {
            report($e);

            return [[], []];
        }

        $names = [];
        $moshafNames = [];

        foreach ($english as $entry) {
            $id = (int) ($entry['id'] ?? 0);
            $name = trim((string) ($entry['name'] ?? ''));

            if ($id > 0 && $name !== '') {
                $names[$id] = $name;
            }

            foreach ((array) ($entry['moshaf'] ?? []) as $moshaf) {
                $moshafNames[(int) ($moshaf['id'] ?? 0)] = (string) ($moshaf['name'] ?? '');
            }
        }

        return [$names, $moshafNames];
    }

    /** The two endpoints disagree on the trailing slash. */
    private static function folderKey(string $url): string
    {
        return rtrim($url, '/');
    }
}
