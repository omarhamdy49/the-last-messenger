<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

final class SunnahApiClient
{
    private float $lastRequestAt = 0.0;

    /** @var array<string, SunnahCollectionData>|null */
    private ?array $collections = null;

    /** @return list<SunnahBookData> */
    public function fetchFortyBooks(): array
    {
        return $this->fetchBooks('forty', false);
    }

    public function fetchCollection(
        SunnahCollectionDefinition $definition,
        ?Closure $progress = null,
    ): SunnahCollectionSnapshot {
        $collection = $this->collectionData($definition->source);
        $books = $this->fetchBooks($definition->source, $definition->hasChapters, $progress);

        return new SunnahCollectionSnapshot($collection, $books);
    }

    public function collectionData(string $name): SunnahCollectionData
    {
        if ($this->collections === null) {
            $this->collections = [];
            foreach ($this->pages('collections') as $payload) {
                $collection = SunnahCollectionData::fromApi($payload);
                $this->collections[$collection->name] = $collection;
            }
        }

        $collection = $this->collections[$name] ?? null;
        if (! $collection instanceof SunnahCollectionData) {
            throw new RuntimeException("Sunnah API collection {$name} is missing.");
        }

        return $collection;
    }

    /** @return list<array<string, mixed>> */
    public function bookIndex(string $collection): array
    {
        $books = $this->pages("collections/{$collection}/books");
        if ($collection === 'shamail') {
            foreach ($books as &$book) {
                if (($book['bookNumber'] ?? null) === '-8') {
                    // The index contains a typo; the official route, returned rows,
                    // and live in-book reference all identify this book as 8b.
                    $book['bookNumber'] = '8b';
                }
            }
            unset($book);
        }

        return $books;
    }

    /** @param array<string, mixed> $book */
    public function fetchBook(string $collection, bool $withChapters, array $book): SunnahBookData
    {
        $number = trim((string) ($book['bookNumber'] ?? ''));
        if ($number === '') {
            throw new RuntimeException("Sunnah API {$collection} book has no bookNumber.");
        }

        $encoded = rawurlencode($number);
        try {
            $hadithPayloads = $this->pages("collections/{$collection}/books/{$encoded}/hadiths");
        } catch (RuntimeException $exception) {
            if (! str_starts_with($exception->getMessage(), 'Sunnah API error payload')) {
                throw $exception;
            }
            $hadithPayloads = match ([$collection, $number]) {
                ['bukhari', '89'] => $this->recoverBukhariBook89((int) ($book['numberOfHadith'] ?? 0)),
                ['bukhari', '96'] => $this->recoverBukhariBook96((int) ($book['numberOfHadith'] ?? 0)),
                ['tirmidhi', '25'] => $this->recoverOneRowPages(
                    'tirmidhi',
                    '25',
                    (int) ($book['numberOfHadith'] ?? 0),
                    [33 => SunnahApiFallbacks::tirmidhi1819()],
                ),
                default => throw $exception,
            };
        }
        try {
            $chapterPayloads = $withChapters
                ? $this->pages("collections/{$collection}/books/{$encoded}/chapters")
                : [];
        } catch (RuntimeException $exception) {
            if (! $withChapters || ! str_starts_with($exception->getMessage(), 'Sunnah API error payload')) {
                throw $exception;
            }
            $chapterPayloads = $this->chaptersFromHadiths($number, $hadithPayloads);
        }
        $chapters = array_map(
            static fn (array $payload): SunnahChapterData => SunnahChapterData::fromApi($payload),
            $chapterPayloads,
        );
        $hadiths = array_map(
            static fn (array $payload): SunnahHadithData => SunnahHadithData::fromApi($payload),
            $hadithPayloads,
        );

        return SunnahBookData::fromApi($book, $hadiths, $chapters);
    }

    /** @return list<array<string, mixed>> */
    private function recoverBukhariBook89(int $expected): array
    {
        if ($expected < 1) {
            throw new RuntimeException('Cannot recover Bukhari book 89 without its declared count.');
        }

        $uri = 'collections/bukhari/books/89/hadiths';
        $rows = [SunnahApiFallbacks::bukhari6940()];
        for ($page = 2; $page <= $expected; $page++) {
            $payload = $this->getJson($uri, ['limit' => 1, 'page' => $page]);
            if (isset($payload['error'])) {
                throw new RuntimeException("Sunnah API error payload for {$uri} page {$page}.");
            }
            $data = $payload['data'] ?? null;
            if (! is_array($data) || count($data) !== 1 || ! is_array($data[0])) {
                throw new RuntimeException("Malformed recovery page {$page} for Bukhari book 89.");
            }
            $rows[] = $data[0];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function recoverBukhariBook96(int $expected): array
    {
        if ($expected < 6) {
            throw new RuntimeException('Cannot recover Bukhari book 96 without its declared count.');
        }

        $uri = 'collections/bukhari/books/96/hadiths';
        $rows = SunnahApiFallbacks::bukhariBook96Opening();
        for ($page = 6; $page <= $expected; $page++) {
            $payload = $this->getJson($uri, ['limit' => 1, 'page' => $page]);
            if (isset($payload['error'])) {
                throw new RuntimeException("Sunnah API error payload for {$uri} page {$page}.");
            }
            $data = $payload['data'] ?? null;
            if (! is_array($data) || count($data) !== 1 || ! is_array($data[0])) {
                throw new RuntimeException("Malformed recovery page {$page} for Bukhari book 96.");
            }
            $rows[] = $data[0];
        }

        if (count($rows) !== $expected) {
            throw new RuntimeException("Recovered Bukhari book 96 count mismatch: expected {$expected}, got ".count($rows).'.');
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fallbacks
     * @return list<array<string, mixed>>
     */
    private function recoverOneRowPages(string $collection, string $book, int $expected, array $fallbacks = []): array
    {
        if ($expected < 1) {
            throw new RuntimeException("Cannot recover {$collection} book {$book} without its declared count.");
        }

        $uri = "collections/{$collection}/books/{$book}/hadiths";
        $rows = [];
        for ($page = 1; $page <= $expected; $page++) {
            $payload = $this->getJson($uri, ['limit' => 1, 'page' => $page]);
            if (isset($payload['error'])) {
                if (! isset($fallbacks[$page])) {
                    throw new RuntimeException("Sunnah API error payload for {$uri} page {$page}.");
                }
                $rows[] = $fallbacks[$page];

                continue;
            }
            $data = $payload['data'] ?? null;
            if (! is_array($data) || count($data) !== 1 || ! is_array($data[0])) {
                throw new RuntimeException("Malformed recovery page {$page} for {$collection} book {$book}.");
            }
            $rows[] = $data[0];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $hadiths
     * @return list<array<string, mixed>>
     */
    private function chaptersFromHadiths(string $bookNumber, array $hadiths): array
    {
        $chapters = [];
        foreach ($hadiths as $hadith) {
            $chapterId = trim((string) ($hadith['chapterId'] ?? ''));
            if ($chapterId === '' || $chapterId === '0.00' || isset($chapters[$chapterId])) {
                continue;
            }

            $localized = [];
            foreach (($hadith['hadith'] ?? []) as $translation) {
                if (! is_array($translation)) {
                    continue;
                }
                $language = (string) ($translation['lang'] ?? '');
                $title = trim((string) ($translation['chapterTitle'] ?? ''));
                if (in_array($language, ['ar', 'en'], true) && $title !== '') {
                    $localized[] = [
                        'lang' => $language,
                        'chapterNumber' => (string) ($translation['chapterNumber'] ?? ''),
                        'chapterTitle' => $title,
                        'intro' => null,
                        'ending' => null,
                    ];
                }
            }
            if ($localized === []) {
                throw new RuntimeException("Cannot reconstruct chapter {$chapterId} from book {$bookNumber} Hadith data.");
            }
            $chapters[$chapterId] = [
                'bookNumber' => $bookNumber,
                'chapterId' => $chapterId,
                'chapter' => $localized,
            ];
        }

        return array_values($chapters);
    }

    /** @return list<SunnahBookData> */
    private function fetchBooks(string $collection, bool $withChapters, ?Closure $progress = null): array
    {
        $books = $this->bookIndex($collection);
        $result = [];

        foreach ($books as $index => $book) {
            $mapped = $this->fetchBook($collection, $withChapters, $book);
            $result[] = $mapped;
            $progress?->__invoke($collection, $mapped->number, $index + 1, count($books), count($mapped->hadiths));
        }

        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function pages(string $uri): array
    {
        $page = 1;
        $rows = [];

        do {
            $payload = $this->getJson($uri, ['limit' => 100, 'page' => $page]);
            if (isset($payload['error']) && is_array($payload['error'])) {
                $code = (int) ($payload['error']['code'] ?? 0);
                throw new RuntimeException("Sunnah API error payload for {$uri}: code {$code}.");
            }
            $data = $payload['data'] ?? null;
            if (! is_array($data)) {
                throw new RuntimeException("Malformed Sunnah API response for {$uri}: data is not an array.");
            }

            foreach ($data as $row) {
                if (! is_array($row)) {
                    throw new RuntimeException("Malformed Sunnah API response for {$uri}: row is not an object.");
                }
                $rows[] = $row;
            }

            $next = $payload['next'] ?? null;
            $page = $next === null ? 0 : (int) $next;
        } while ($page > 0);

        return $rows;
    }

    /**
     * @param  array<string, int|string>  $query
     * @return array<string, mixed>
     */
    private function getJson(string $uri, array $query): array
    {
        $apiKey = (string) config('sunnah.api.key');
        if ($apiKey === '') {
            throw new RuntimeException('SUNNAH_API_KEY is not configured.');
        }

        $attempts = max(1, (int) config('sunnah.api.retry_attempts', 3));
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $this->throttle();

            try {
                $response = Http::baseUrl(rtrim((string) config('sunnah.api.base_url'), '/').'/')
                    ->acceptJson()
                    ->withHeaders(['X-API-Key' => $apiKey, 'User-Agent' => 'TheLastMessengerHadithImporter/1.0'])
                    ->timeout((int) config('sunnah.api.timeout', 30))
                    ->get($uri, $query);
            } catch (ConnectionException $exception) {
                if ($attempt === $attempts) {
                    throw new RuntimeException("Sunnah API connection failed for {$uri}.", 0, $exception);
                }
                $this->backoff($attempt, null);

                continue;
            }

            $this->lastRequestAt = microtime(true);
            if ($response->successful()) {
                return $this->decode($response, $uri);
            }
            if (! $this->retryable($response) || $attempt === $attempts) {
                throw new RuntimeException("Sunnah API returned HTTP {$response->status()} for {$uri}.");
            }

            $this->backoff($attempt, $response);
        }

        throw new RuntimeException("Sunnah API request failed for {$uri}.");
    }

    private function throttle(): void
    {
        $delayMs = max(0, (int) config('sunnah.api.rate_limit.delay_between_requests', 2000));
        $remainingMs = $delayMs - ((microtime(true) - $this->lastRequestAt) * 1000);

        if ($this->lastRequestAt > 0 && $remainingMs > 0) {
            usleep((int) ceil($remainingMs) * 1000);
        }
    }

    private function retryable(Response $response): bool
    {
        return $response->status() === 429 || $response->serverError();
    }

    private function backoff(int $attempt, ?Response $response): void
    {
        $retryAfter = $response?->header('Retry-After');
        $delayMs = is_string($retryAfter) && ctype_digit($retryAfter)
            ? ((int) $retryAfter * 1000)
            : (int) config('sunnah.api.retry_delay', 1000) * $attempt;

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }

    /** @return array<string, mixed> */
    private function decode(Response $response, string $uri): array
    {
        try {
            $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Sunnah API returned invalid JSON for {$uri}.", 0, $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException("Sunnah API returned an invalid payload for {$uri}.");
        }

        return $payload;
    }
}
