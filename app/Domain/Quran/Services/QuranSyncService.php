<?php

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\SurahInfo;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Models\VerseTranslation;
use App\Utils\HttpUtil;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class QuranSyncService
{
    private const CACHE_KEY = 'quran_api_access_token';

    private const TOKEN_TTL = 3500; // Cache for slightly less than 1 hour

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_SECONDS = 4;

    private const CHAPTERS_BEFORE_PAUSE = 20;

    private const PAUSE_DURATION_SECONDS = 10;

    /** @var callable|null */
    private mixed $onProgress = null;

    public function __construct()
    {
    }

    public function syncSurah(?callable $onProgress = null, int $fromChapter = 1, int $toChapter = 114): array
    {
        $this->onProgress = $onProgress;

        $this->notify($onProgress, 'Fetching chapters list...', 'info');

        $response = $this->requestWithRetry(fn() => $this->quranClient()->get('chapters'));

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surahs from Quran API: ' . $response->getBody()
            );
        }

        $result = [
            'surahs' => 0,
            'surah_info' => 0,
            'verses' => 0,
            'translations' => 0,
            'errors' => [],
        ];

        $allChapters = $response->json()['chapters'];

        // Filter chapters based on from/to range
        $chapters = array_filter($allChapters, fn($ch) => $ch['id'] >= $fromChapter && $ch['id'] <= $toChapter);
        $totalSurahs = count($chapters);

        $this->notify($onProgress, "Found {$totalSurahs} chapters to sync", 'info');

        $processedCount = 0;

        foreach ($chapters as $surahData) {
            $surahNumber = $surahData['id'];

            try {
                DB::beginTransaction();

                // Step 1: Save surah
                $this->notify($onProgress, "[{$surahNumber}/{$toChapter}] Saving surah {$surahData['name_simple']}...", 'step');
                $surah = $this->saveSurah($surahData);
                $result['surahs']++;

                // Step 2: Get and save surah info
                $this->notify($onProgress, "[{$surahNumber}/{$toChapter}] Fetching surah info...", 'substep');
                $infoData = $this->getSurahInfo($surahData['id']);
                $this->saveSurahInfo($surah->id, $infoData);
                $result['surah_info']++;

                $tafsir = $this->getAllSurahTafsir($surahData['id']);
                // Step 3: Get and save verses with translations
                $this->notify($onProgress, "[{$surahNumber}/{$toChapter}] Fetching verses ({$surahData['verses_count']} verses)...", 'substep');
                $versesData = $this->getSurahVerses($surahData['id']);

                $versesCount = count($versesData);
                foreach ($versesData as $verseData) {
                    $verse = $this->saveVerse($surah->id, $verseData);
                    // save verse tafsir
                    $this->saveVerseTafsir($verse->id, $verse->getVerseKey(), $tafsir);
                    $result['verses']++;

                    // Save translations
                    foreach ($verseData['quran_verse_translation'] as $translationData) {
                        $this->saveVerseTranslation($verse->id, $translationData);

                        $result['translations']++;
                    }
                }

                $this->notify($onProgress, "[{$surahNumber}/{$toChapter}] Saved {$versesCount} verses", 'substep');

                DB::commit();

                $this->notify($onProgress, "[{$surahNumber}/{$toChapter}] Saved surah {$surahData['name_simple']}", 'success');

                Log::info("Synced surah {$surah->id}: {$surahData['name_simple']}");

                // Pause every N chapters to avoid rate limiting
                $processedCount++;
                if ($processedCount % self::CHAPTERS_BEFORE_PAUSE === 0 && $surahNumber < $toChapter) {
                    $this->notify($onProgress, "Pausing for " . self::PAUSE_DURATION_SECONDS . " seconds after {$processedCount} chapters...", 'info');
                    sleep(self::PAUSE_DURATION_SECONDS);
                }

            } catch (Throwable $e) {
                DB::rollBack();
                $result['errors'][] = "Surah {$surahData['id']}: {$e->getMessage()}";
                $this->notify($onProgress, "[{$surahNumber}/{$toChapter}] Failed to save surah {$surahData['name_simple']}: {$e->getMessage()}", 'error');
                Log::error("Failed to sync surah {$surahData['id']}: {$e->getMessage()}");
            }
        }

        return $result;
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

        if (!$surah) {
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
                    'lang' => $arVerseTafsir["language"],
                ],
                [
                    'lang' => $arVerseTafsir["language"],
                    'verse_key' => $verseKey,
                    'text' => $arVerseTafsir['text'],
                    'active' => true
                ]
            );
        } else {
            Log::warning("Arabic tafsir not found for verse_key: {$verseKey}");
        }

        if ($enVerseTafsir) {
            VerseTafsir::updateOrCreate(
                [
                    'verse_id' => $verseId,
                    'lang' => $enVerseTafsir["language"],
                ],
                [
                    'lang' => $enVerseTafsir["language"],
                    'verse_key' => $verseKey,
                    'text' => $enVerseTafsir['text'],
                    'active' => true
                ]
            );
        } else {
            Log::warning("English tafsir not found for verse_key: {$verseKey}");
        }
    }

    public function getSurahInfo(int $surahNumber): array
    {
        $response = $this->requestWithRetry(
            fn() => $this->quranClient()->get("chapters/{$surahNumber}/info")
        );

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surah info from Quran API: ' . $response->getBody()
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
    public function getAllSurahTafsir(int $surahNumber): array
    {
        $arabic_tafsir_id = 15;
        $english_tafsir_id = 169;

        $arTafsir = $this->getSurahTafsir($surahNumber, $arabic_tafsir_id);
        $enTafsir = $this->getSurahTafsir($surahNumber, $english_tafsir_id);
        return [
            "ar" => collect($arTafsir),
            "en" => collect($enTafsir),
        ];

    }

    /**
     * @throws Throwable
     */
    public function getSurahTafsir(int $surahNumber, int $tafsir_id): array
    {

        $response = $this->requestWithRetry(
            fn() => $this->quranClient()->get("tafsirs/{$tafsir_id}/by_chapter/{$surahNumber}",["per_page"=>2000])
        );

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surah tafsir from Quran API: ' . $response->getBody()
            );
        }

        return array_map(function ($item) {
            $item["language"] = ($item["language_id"] == 9) ? "ar" : "en";
            return $item;
        }, $response->json()['tafsirs']);

    }

    public function getSurahVerses(int $surahNumber): array
    {
        $response = $this->requestWithRetry(
            fn() => $this->quranClient()->get("verses/by_chapter/{$surahNumber}", [
                'fields' => 'text_uthmani,chapter_id,hizb_number,text_imlaei_simple',
                'per_page' => '1000',
                'translations' => '20,57',
            ])
        );

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surah verses from Quran API: ' . $response->getBody()
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

    private function quranClient(): HttpUtil
    {
        $accessToken = $this->getAccessToken();
        $clientId = config('services.quran.client_id');

        $client = new HttpUtil(config('services.quran.api_url'));
        $client->setDefaultHeaders([
            'x-auth-token' => $accessToken,
            'x-client-id' => $clientId,
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

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to authenticate with Quran API: ' . $response->getBody()
            );
        }

        $data = $response->json();

        if (!isset($data['access_token'])) {
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
                $isRetryable = str_contains($message, 'cURL error 6')
                    || str_contains($message, 'cURL error 7')
                    || str_contains($message, 'cURL error 28')
                    || str_contains($message, 'Could not resolve host')
                    || str_contains($message, 'Connection refused')
                    || str_contains($message, 'timed out')
                    || str_contains($message, 'rate limit')
                    || str_contains($message, '429');

                if ($isRetryable && $attempt < self::MAX_RETRIES) {
                    Log::warning("API request failed (attempt {$attempt}): {$message}");

                    $this->notify(
                        $this->onProgress,
                        "Connection error, retrying in " . self::RETRY_DELAY_SECONDS . " seconds... (attempt {$attempt}/" . self::MAX_RETRIES . ')',
                        'warning'
                    );

                    sleep(self::RETRY_DELAY_SECONDS);

                    continue;
                }

                throw $e;
            }
        }

        throw $lastException;
    }
}
