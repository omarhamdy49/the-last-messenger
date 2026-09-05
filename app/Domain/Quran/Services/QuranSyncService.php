<?php

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\SurahInfo;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Models\VerseTranslation;
use App\Utils\HttpUtil;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class QuranSyncService
{
    private const CACHE_KEY = 'quran_api_access_token';

    private const TOKEN_TTL = 3500; // Cache for slightly less than 1 hour

    private const MAX_RETRIES = 5;

    private const RETRY_DELAY_SECONDS = 5;

    private const CHAPTERS_BEFORE_PAUSE = 10;

    private const PAUSE_DURATION_SECONDS = 15;

    private const MIN_REQUEST_DELAY_MS = 800;

    private const MAX_REQUEST_DELAY_MS = 2500;

    private const TAFSIR_PAGE_SIZE = 50; // Paginate tafsir requests

    private const DEFAULT_TIMEOUT = 30;

    private const LARGE_REQUEST_TIMEOUT = 120; // 2 minutes for large requests

    private const USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
    ];

    /** @var callable|null */
    private mixed $onProgress = null;

    private int $requestCount = 0;

    public function __construct() {}

    private function getRandomUserAgent(): string
    {
        return self::USER_AGENTS[array_rand(self::USER_AGENTS)];
    }

    private function randomDelay(): void
    {
        $delay = random_int(self::MIN_REQUEST_DELAY_MS, self::MAX_REQUEST_DELAY_MS);
        usleep($delay * 1000);
    }

    public function syncSurah(?callable $onProgress = null, int $fromChapter = 1, int $toChapter = 114): array
    {
        $this->onProgress = $onProgress;

        $chapters = $this->fetchChaptersList($fromChapter, $toChapter);
        $totalSurahs = count($chapters);

        $this->notify($onProgress, "Found {$totalSurahs} chapters to sync", 'info');

        $result = [
            'surahs' => 0,
            'surah_info' => 0,
            'verses' => 0,
            'translations' => 0,
            'errors' => [],
        ];

        $processedCount = 0;

        foreach ($chapters as $surahData) {
            $surahNumber = $surahData['id'];

            try {
                $this->processSurah($surahData, $toChapter, $result);

                // Pause every N chapters to avoid rate limiting
                $processedCount++;
                if ($processedCount % self::CHAPTERS_BEFORE_PAUSE === 0 && $surahNumber < $toChapter) {
                    $this->notify($onProgress, 'Pausing for '.self::PAUSE_DURATION_SECONDS." seconds after {$processedCount} chapters...", 'info');
                    sleep(self::PAUSE_DURATION_SECONDS);
                }
            } catch (Throwable $e) {
                $result['errors'][] = "Surah {$surahData['id']}: {$e->getMessage()}";
                $this->notify($onProgress, "[{$surahNumber}/{$toChapter}] Failed to save surah {$surahData['name_simple']}: {$e->getMessage()}", 'error');
                Log::error("Failed to sync surah {$surahData['id']}: {$e->getMessage()}");
            }
        }

        return $result;
    }

    /**
     * Fetch all chapters from the API and return only those within the requested range.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException
     */
    private function fetchChaptersList(int $fromChapter, int $toChapter): array
    {
        $this->notify($this->onProgress, 'Fetching chapters list...', 'info');

        $response = $this->requestWithRetry(fn () => $this->quranClient()->get('chapters'));

        if (! $response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surahs from Quran API: '.$response->getBody()
            );
        }

        $allChapters = $response->json()['chapters'];

        return array_values(
            array_filter($allChapters, fn ($ch) => $ch['id'] >= $fromChapter && $ch['id'] <= $toChapter)
        );
    }

    /**
     * Persist one surah and all its related data inside a single DB transaction.
     * Rolls back and re-throws on failure so the caller can record the error.
     *
     * @throws Throwable
     */
    private function processSurah(array $surahData, int $toChapter, array &$result): void
    {
        $surahNumber = $surahData['id'];

        DB::beginTransaction();

        try {
            // Step 1: Save surah
            $this->notify($this->onProgress, "[{$surahNumber}/{$toChapter}] Saving surah {$surahData['name_simple']}...", 'step');
            $surah = $this->saveSurah($surahData);
            $result['surahs']++;

            // Step 2: Get and save surah info
            $this->notify($this->onProgress, "[{$surahNumber}/{$toChapter}] Fetching surah info...", 'substep');
            $infoData = $this->getSurahInfo($surahData['id']);
            $this->saveSurahInfo($surah->id, $infoData);
            $result['surah_info']++;

            // Step 3: Get tafsir data (paginated to avoid timeout)
            $tafsir = $this->getAllSurahTafsir($surahData['id'], $surahData['verses_count']);

            // Step 4: Get and save verses with translations
            $this->notify($this->onProgress, "[{$surahNumber}/{$toChapter}] Fetching verses ({$surahData['verses_count']} verses)...", 'substep');
            $versesData = $this->getSurahVerses($surahData['id']);
            $versesCount = count($versesData);

            foreach ($versesData as $verseData) {
                $this->saveVerseWithRelations($surah, $verseData, $tafsir, $result);
            }

            $this->notify($this->onProgress, "[{$surahNumber}/{$toChapter}] Saved {$versesCount} verses", 'substep');

            DB::commit();

            $this->notify($this->onProgress, "[{$surahNumber}/{$toChapter}] Saved surah {$surahData['name_simple']}", 'success');
            Log::info("Synced surah {$surah->id}: {$surahData['name_simple']}");
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Save a single verse and all its associated tafsir and translation records.
     */
    private function saveVerseWithRelations(Surah $surah, array $verseData, array $tafsir, array &$result): void
    {
        $verse = $this->saveVerse($surah->id, $verseData);
        $this->saveVerseTafsir($verse->id, $verse->getVerseKey(), $tafsir);
        $result['verses']++;

        foreach ($verseData['quran_verse_translation'] as $translationData) {
            $this->saveVerseTranslation($verse->id, $translationData);
            $result['translations']++;
        }
    }

    private function notify(?callable $callback, string $message, string $type = 'info'): void
    {
        if ($callback !== null) {
            $callback($message, $type);
        }
    }

    private function saveSurah(array $data): Surah
    {
        $surah = Surah::find($data['id']);

        if (! $surah) {
            $surah = new Surah;
            $surah->id = $data['id'];
        }

        $surah->fill([
            'revelation_place' => $data['revelation_place'],
            'revelation_order' => $data['revelation_order'],
            'bismillah_pre' => $data['bismillah_pre'],
            'name_ar' => $data['name_arabic'],
            'transliteration' => strtolower($data['name_simple']),
            'name_en' => strtolower($data['translated_name']['name']),
            'verses_count' => $data['verses_count'],
            'pages' => $data['pages'],
        ]);

        $surah->save();

        return $surah;
    }

    private function saveSurahInfo(int $surahId, array $data): SurahInfo
    {
        return SurahInfo::updateOrCreate(
            [
                'surah_id' => $surahId,
                'lang' => $data['lang'],
            ],
            [
                'desc' => $data['desc'],
                'content' => $data['content'],
                'source' => $data['source'],
                'active' => true,
            ]
        );
    }

    private function saveVerse(int $surahId, array $data): Verse
    {
        return Verse::updateOrCreate(
            [
                'surah_id' => $surahId,
                'verse_number' => $data['verse_number'],
            ],
            [
                'verse_key' => $data['verse_key'],
                'juz_number' => $data['juz_number'],
                'hizb_number' => $data['hizb_number'],
                'rub_el_hizb_number' => $data['rub_el_hizb_number'],
                'ruku_number' => $data['ruku_number'],
                'manzil_number' => $data['manzil_number'],
                'sajdah_number' => $data['sajdah_number'],
                'page_number' => $data['page_number'],
            ]
        );
    }

    private function saveVerseTranslation(int $verseId, array $data): VerseTranslation
    {
        return VerseTranslation::updateOrCreate(
            [
                'verse_id' => $verseId,
                'lang' => $data['lan'],
                // Legacy, edition-less row. Without this the match would hit an
                // imported edition in the same language and overwrite its text.
                'resource_id' => null,
            ],
            [
                'text' => $data['text'],
                'text_notashkeel' => $data['text_notashkeel'],
            ]
        );
    }

    private function saveVerseTafsir(int $verseId, string $verseKey, array $tafsirData): void
    {
        $arVerseTafsir = $tafsirData['ar']->firstWhere('verse_key', $verseKey);
        $enVerseTafsir = $tafsirData['en']->firstWhere('verse_key', $verseKey);

        if ($arVerseTafsir) {
            VerseTafsir::updateOrCreate(
                [
                    'verse_id' => $verseId,
                    'lang' => $arVerseTafsir['language'],
                    // Legacy, edition-less row — see saveVerseTranslation().
                    'resource_id' => null,
                ],
                [
                    'lang' => $arVerseTafsir['language'],
                    'verse_key' => $verseKey,
                    'text' => $arVerseTafsir['text'],
                    'active' => true,
                ]
            );
        } else {
            Log::warning("Arabic tafsir not found for verse_key: {$verseKey}");
        }

        if ($enVerseTafsir) {
            VerseTafsir::updateOrCreate(
                [
                    'verse_id' => $verseId,
                    'lang' => $enVerseTafsir['language'],
                    // Legacy, edition-less row — see saveVerseTranslation().
                    'resource_id' => null,
                ],
                [
                    'lang' => $enVerseTafsir['language'],
                    'verse_key' => $verseKey,
                    'text' => $enVerseTafsir['text'],
                    'active' => true,
                ]
            );
        } else {
            Log::warning("English tafsir not found for verse_key: {$verseKey}");
        }
    }

    public function getSurahInfo(int $surahNumber): array
    {
        $response = $this->requestWithRetry(
            fn () => $this->quranClient()->get("chapters/{$surahNumber}/info")
        );

        if (! $response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surah info from Quran API: '.$response->getBody()
            );
        }

        $data = $response->json()['chapter_info'];

        return [
            'surah_id' => $data['chapter_id'],
            'lang' => substr($data['language_name'], 0, 2),
            'desc' => $data['short_text'],
            'content' => $data['text'],
            'source' => $data['source'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function getAllSurahTafsir(int $surahNumber, int $versesCount): array
    {
        $arabic_tafsir_id = 15;
        $english_tafsir_id = 169;

        $this->notify($this->onProgress, "   ├─ Fetching Arabic tafsir ({$versesCount} verses)...", 'substep');
        $arTafsir = $this->getSurahTafsirPaginated($surahNumber, $arabic_tafsir_id, $versesCount);

        $this->notify($this->onProgress, "   ├─ Fetching English tafsir ({$versesCount} verses)...", 'substep');
        $enTafsir = $this->getSurahTafsirPaginated($surahNumber, $english_tafsir_id, $versesCount);

        return [
            'ar' => collect($arTafsir),
            'en' => collect($enTafsir),
        ];
    }

    /**
     * Fetch tafsir with pagination to avoid timeout on large surahs
     *
     * @throws Throwable
     */
    public function getSurahTafsirPaginated(int $surahNumber, int $tafsirId, int $totalVerses): array
    {
        $allTafsirs = [];
        $page = 1;
        $perPage = self::TAFSIR_PAGE_SIZE;
        $totalPages = (int) ceil($totalVerses / $perPage);

        while ($page <= $totalPages) {
            $response = $this->requestWithRetry(
                fn () => $this->quranClient(self::LARGE_REQUEST_TIMEOUT)->get(
                    "tafsirs/{$tafsirId}/by_chapter/{$surahNumber}",
                    [
                        'per_page' => $perPage,
                        'page' => $page,
                    ]
                )
            );

            if (! $response->isSuccessful()) {
                throw new \RuntimeException(
                    "Failed to fetch tafsir page {$page} from Quran API: ".$response->getBody()
                );
            }

            $data = $response->json();
            $tafsirs = $data['tafsirs'] ?? [];

            if (empty($tafsirs)) {
                break;
            }

            foreach ($tafsirs as $item) {
                $item['language'] = ($item['language_id'] == 9) ? 'ar' : 'en';
                $allTafsirs[] = $item;
            }

            // Check if we got all items
            $pagination = $data['pagination'] ?? null;
            if ($pagination) {
                $totalPages = $pagination['total_pages'] ?? $totalPages;
            }

            $page++;

            // Small delay between pages to avoid rate limiting
            if ($page <= $totalPages) {
                usleep(300000); // 300ms delay between pages
            }
        }

        return $allTafsirs;
    }

    /**
     * Legacy method for backward compatibility
     *
     * @throws Throwable
     */
    public function getSurahTafsir(int $surahNumber, int $tafsir_id): array
    {
        return $this->getSurahTafsirPaginated($surahNumber, $tafsir_id, 300);
    }

    public function getSurahVerses(int $surahNumber): array
    {
        $response = $this->requestWithRetry(
            fn () => $this->quranClient()->get("verses/by_chapter/{$surahNumber}", [
                'fields' => 'text_uthmani,chapter_id,hizb_number,text_imlaei_simple',
                'per_page' => '1000',
                'translations' => '20,57',
            ])
        );

        if (! $response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surah verses from Quran API: '.$response->getBody()
            );
        }

        $data = [];
        foreach ($response->json()['verses'] as $verse) {
            $data[] = [
                'surah_id' => $surahNumber,
                'verse_number' => $verse['verse_number'],
                'verse_key' => $verse['verse_key'],
                'juz_number' => $verse['juz_number'],
                'hizb_number' => $verse['hizb_number'],
                'rub_el_hizb_number' => $verse['rub_el_hizb_number'] ?? null,
                'ruku_number' => $verse['ruku_number'] ?? null,
                'manzil_number' => $verse['manzil_number'] ?? null,
                'sajdah_number' => $verse['sajdah_number'] ?? null,
                'page_number' => $verse['page_number'],
                'quran_verse_translation' => [
                    [
                        'lan' => 'ar',
                        'text' => $verse['text_uthmani'],
                        'text_notashkeel' => $verse['text_imlaei_simple'] ?? null,
                    ],
                    [
                        'lan' => 'en',
                        'text' => $verse['translations'][0]['text'] ?? null,
                        'text_notashkeel' => $verse['translations'][1]['text'] ?? null,
                    ],
                ],
            ];
        }

        return $data;

    }

    private function quranClient(int $timeout = self::DEFAULT_TIMEOUT): HttpUtil
    {
        // Add random delay before each request to avoid detection
        $this->randomDelay();

        $accessToken = $this->getAccessToken();
        $clientId = config('services.quran.client_id');
        $userAgent = $this->getRandomUserAgent();

        $this->requestCount++;
        Log::debug("Request #{$this->requestCount} using User-Agent: ".substr($userAgent, 0, 50).'...');

        $client = new HttpUtil(config('services.quran.api_url'), [
            'timeout' => $timeout,
            'connect_timeout' => 30,
        ]);
        $client->setDefaultHeaders([
            'x-auth-token' => $accessToken,
            'x-client-id' => $clientId,
            'User-Agent' => $userAgent,
            'Accept-Language' => 'en-US,en;q=0.9,ar;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Cache-Control' => 'no-cache',
        ]);

        return $client;
    }

    /**
     * Get access token from cache or authenticate
     */
    private function getAccessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, self::TOKEN_TTL, function () {
            return $this->authenticate();
        });
    }

    /**
     * Authenticate with Quran API using OAuth2 client credentials
     */
    private function authenticate(): string
    {
        $authUrl = config('services.quran.auth_url');
        $clientId = config('services.quran.client_id');
        $clientSecret = config('services.quran.client_secret');

        $client = HttpUtil::withBasicAuth($authUrl, $clientId, $clientSecret);

        // Override Content-Type for form request
        $response = $client->postForm('', [
            'grant_type' => 'client_credentials',
            'scope' => 'content',
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        if (! $response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to authenticate with Quran API: '.$response->getBody()
            );
        }

        $data = $response->json();

        if (! isset($data['access_token'])) {
            throw new \RuntimeException('Access token not found in authentication response');
        }

        return $data['access_token'];
    }

    /**
     * Execute request with retry on connection/rate limit errors
     */
    private function requestWithRetry(callable $request): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                return $request();
            } catch (Throwable $e) {
                $lastException = $e;
                $message = $e->getMessage();

                // Check if it's a retryable error
                $isRetryable = str_contains($message, 'cURL error 6')   // Could not resolve host
                    || str_contains($message, 'cURL error 7')           // Connection refused
                    || str_contains($message, 'cURL error 28')          // Operation timed out
                    || str_contains($message, 'cURL error 35')          // SSL connect error
                    || str_contains($message, 'cURL error 52')          // Empty reply from server
                    || str_contains($message, 'cURL error 56')          // Recv failure
                    || str_contains($message, 'Could not resolve host')
                    || str_contains($message, 'Connection refused')
                    || str_contains($message, 'Connection reset')
                    || str_contains($message, 'timed out')
                    || str_contains($message, 'Operation timed out')
                    || str_contains($message, 'rate limit')
                    || str_contains($message, '429')
                    || str_contains($message, '502')                    // Bad Gateway
                    || str_contains($message, '503')                    // Service Unavailable
                    || str_contains($message, '504');                   // Gateway Timeout

                if ($isRetryable && $attempt < self::MAX_RETRIES) {
                    Log::warning("API request failed (attempt {$attempt}): {$message}");

                    // Exponential backoff: 5s, 10s, 20s, 40s...
                    $delay = self::RETRY_DELAY_SECONDS * pow(2, $attempt - 1);
                    $delay = min($delay, 60); // Cap at 60 seconds

                    $this->notify(
                        $this->onProgress,
                        "⚠️  Retry in {$delay}s... (attempt {$attempt}/".self::MAX_RETRIES.')',
                        'warning'
                    );

                    sleep($delay);

                    continue;
                }

                throw $e;
            }
        }

        throw $lastException;
    }
}
