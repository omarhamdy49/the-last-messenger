<?php

declare(strict_types=1);

namespace App\Services\Sunnah;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use stdClass;

class SunnahApiClient
{
    private Client $client;

    private string $baseUrl;

    private string $apiKey;

    private int $rateLimitDelay = 5000; // milliseconds (5 seconds)

    private float $lastRequestTime = 0;

    private int $delayOn429 = 30000; // milliseconds (30 seconds when rate limited)

    /**
     * List of common user agents to rotate through
     */
    private const USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/91.0.864.59',
        'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:89.0) Gecko/20100101 Firefox/89.0',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36',
    ];

    public function __construct()
    {
        $this->baseUrl = config('sunnah.api.base_url');
        $this->apiKey = config('sunnah.api.key');
        $this->rateLimitDelay = config('sunnah.api.rate_limit.delay_between_requests', 2000);
        $this->delayOn429 = config('sunnah.api.rate_limit.delay_on_429', 30000);

        // Create Guzzle client with retry middleware
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('sunnah.api.timeout', 30),
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'User-Agent' => $this->getRandomUserAgent(),
                'Referer' => 'https://sunnah.com/',
            ],
        ]);
    }

    /**
     * Get a random user agent from the list
     */
    private function getRandomUserAgent(): string
    {
        return self::USER_AGENTS[array_rand(self::USER_AGENTS)];
    }

    /**
     * Get all collections (hadith books)
     */
    public function getCollections(int $limit = 50, int $page = 1): stdClass
    {
        return $this->makeRequestWithRetry('GET', 'collections', [
            'query' => [
                'limit' => $limit,
                'page' => $page,
            ],
        ]);
    }

    /**
     * Get a specific collection by name
     */
    public function getCollection(string $collectionName): stdClass
    {
        return $this->makeRequestWithRetry('GET', "collections/{$collectionName}");
    }

    /**
     * Get all books in a collection
     */
    public function getBooks(string $collectionName, int $limit = 50, int $page = 1): stdClass
    {
        return $this->makeRequestWithRetry('GET', "collections/{$collectionName}/books", [
            'query' => [
                'limit' => $limit,
                'page' => $page,
            ],
        ]);
    }

    /**
     * Get a specific book in a collection
     */
    public function getBook(string $collectionName, string $bookNumber): stdClass
    {
        return $this->makeRequestWithRetry('GET', "collections/{$collectionName}/books/{$bookNumber}");
    }

    /**
     * Get all chapters in a book
     */
    public function getChapters(string $collectionName, string $bookNumber, int $limit = 50, int $page = 1): stdClass
    {
        return $this->makeRequestWithRetry('GET', "collections/{$collectionName}/books/{$bookNumber}/chapters", [
            'query' => [
                'limit' => $limit,
                'page' => $page,
            ],
        ]);
    }

    /**
     * Get a specific chapter
     */
    public function getChapter(string $collectionName, string $bookNumber, string|float $chapterId): stdClass
    {
        return $this->makeRequestWithRetry('GET', "collections/{$collectionName}/books/{$bookNumber}/chapters/{$chapterId}");
    }

    /**
     * Get all hadiths in a book
     */
    public function getHadithsByBook(string $collectionName, string $bookNumber, int $limit = 50, int $page = 1): stdClass
    {
        return $this->makeRequestWithRetry('GET', "collections/{$collectionName}/books/{$bookNumber}/hadiths", [
            'query' => [
                'limit' => $limit,
                'page' => $page,
            ],
        ]);
    }

    /**
     * Get a specific hadith by URN
     */
    public function getHadithByUrn(int|string $urn): stdClass
    {
        return $this->makeRequestWithRetry('GET', "hadiths/{$urn}");
    }

    /**
     * Get a specific hadith by collection and hadith number
     */
    public function getHadith(string $collectionName, string $hadithNumber): stdClass
    {
        return $this->makeRequestWithRetry('GET', "collections/{$collectionName}/hadiths/{$hadithNumber}");
    }

    /**
     * Get all hadiths with filters
     */
    public function getHadiths(
        ?string $collection = null,
        ?string $bookNumber = null,
        ?float $chapterId = null,
        ?string $hadithNumber = null,
        int $limit = 50,
        int $page = 1
    ): stdClass {
        $query = [
            'limit' => $limit,
            'page' => $page,
        ];

        if ($collection) {
            $query['collection'] = $collection;
        }
        if ($bookNumber) {
            $query['bookNumber'] = $bookNumber;
        }
        if ($chapterId) {
            $query['chapterId'] = $chapterId;
        }
        if ($hadithNumber) {
            $query['hadithNumber'] = $hadithNumber;
        }

        return $this->makeRequestWithRetry('GET', 'hadiths', ['query' => $query]);
    }

    /**
     * Get a random hadith
     */
    public function getRandomHadith(): stdClass
    {
        return $this->makeRequestWithRetry('GET', 'hadiths/random');
    }

    /**
     * Apply rate limiting by enforcing minimum delay between requests
     */
    private function applyRateLimit(): void
    {
        $now = microtime(true) * 1000; // Convert to milliseconds
        $elapsed = $now - $this->lastRequestTime;

        if ($elapsed < $this->rateLimitDelay) {
            $sleep = (int) ceil($this->rateLimitDelay - $elapsed);
            usleep($sleep * 1000); // Convert milliseconds to microseconds
            Log::debug("Rate limiting: slept for {$sleep}ms");
        }

        $this->lastRequestTime = microtime(true) * 1000;
    }

    /**
     * Make API request with retry logic for 429 errors
     */
    private function makeRequestWithRetry(string $method, string $endpoint, array $options = [], int $maxRetries = 3): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                $this->applyRateLimit();
                $response = $this->client->request($method, $endpoint, $options);

                return json_decode((string) $response->getBody());
            } catch (RequestException $e) {
                $lastException = $e;
                $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;

                if ($statusCode === 429) {
                    // Rate limited - exponential backoff
                    $attempt++;
                    $delay = $this->delayOn429 * pow(2, $attempt - 1); // Exponential backoff: 30s, 60s, 120s
                    $delaySec = round($delay / 1000, 1);

                    Log::warning("429 Too Many Requests - Waiting {$delaySec}s before retry (attempt {$attempt}/{$maxRetries})", [
                        'endpoint' => $endpoint,
                        'attempt' => $attempt,
                        'delay_ms' => $delay,
                    ]);

                    usleep((int) ($delay * 1000)); // Convert to microseconds
                } else {
                    // Other error - don't retry
                    throw $e;
                }
            }
        }

        // All retries exhausted
        throw $lastException;
    }

    /**
     * Paginate through all results using a callback
     *
     * @param  callable  $callback  Function that receives page data and returns true to continue
     * @param  int  $limit  Items per page
     */
    public function paginateAll(callable $callback, string $endpoint, int $limit = 50): void
    {
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            try {
                // Make the request based on endpoint
                if (str_contains($endpoint, '{collection}')) {
                    // Will be replaced by the callback function
                    break;
                }

                $response = $this->client->get($endpoint, [
                    'query' => [
                        'limit' => $limit,
                        'page' => $page,
                    ],
                ]);

                $data = json_decode((string) $response->getBody());

                $shouldContinue = $callback($data);

                if (! $shouldContinue || ! isset($data->next) || $data->next === null) {
                    $hasMore = false;
                } else {
                    $page = $data->next;
                }
            } catch (RequestException $e) {
                Log::error('Sunnah API pagination error', [
                    'endpoint' => $endpoint,
                    'page' => $page,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $this->applyRateLimit();
            $response = $this->client->get('collections', ['query' => ['limit' => 1]]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            Log::error('Sunnah API connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
