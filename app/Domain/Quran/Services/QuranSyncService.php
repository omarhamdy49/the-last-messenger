<?php

namespace App\Domain\Quran\Services;

use App\Utils\HttpUtil;
use Illuminate\Support\Facades\Cache;

class QuranSyncService
{
    private const CACHE_KEY = 'quran_api_access_token';

    private const TOKEN_TTL = 3500; // Cache for slightly less than 1 hour

    public function __construct()
    {
    }

    public function syncSurah()
    {
        $client = $this->quranClient();
        $response = $client->get('/chapters');

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surahs from Quran API: ' . $response->getBody()
            );
        }

        $data = $response->json();

        // Process and store the surah data as needed
        foreach ($data['chapters'] as $surah) {

            // filter surah data
            $data = [
                "revelation_place" => $surah['revelation_place'],
                "revelation_order" => $surah['revelation_order'],
                "bismillah_pre" => $surah['bismillah_pre'],
                "name_ar" => $surah['name_arabic'],
                "translation" => strtolower($surah['name_simple']),
                "name_en" => strtolower($surah['translated_name']["name"]),
                "verses_count" => $surah['verses_count'],
                "pages" => $surah['pages']
            ];
            // get surah info
            $data["info"] = $this->getSurahInfo($surah['id']);
            //get surah verses
            $data["verses"] = $this->getSurahVerses($surah['id']);
            // get surah tafsir

        }
    }

    public function getSurahInfo(int $surahNumber): array
    {
        $response = $this->quranClient()->get("/chapters/{$surahNumber}/info");

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surah info from Quran API: ' . $response->getBody()
            );
        }

        $data = $response->json()["chapter_info"];
        return [
            "surah_id" => $data["chapter_id"],
            "lang" => substr($data["language_name"], 0, 2),
            "desc" => $data["short_text"],
            "content" => $data["text"],
            "source" => $data["source"],
        ];

    }

    public function getSurahVerses(int $surahNumber): array
    {
        $response = $this->quranClient()->get("/content/api/v4/verses/by_chapter/{$surahNumber}", [
//            'words' => true,
            'fields' => 'text_uthmani,chapter_id,hizb_number,text_imlaei_simple',
            'per_page' => '1000',
            'translations' => '85,57'
        ]);

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(
                'Failed to fetch surah verses from Quran API: ' . $response->getBody()
            );
        }

        $data = [];
        foreach ($response->json()["verses"] as $verse) {
            $data[] = [
                "surah_id" => $surahNumber,
                "verse_number" => $verse["verse_number"],
                "verse_key" => $verse["verse_key"],
                "juz_number" => $verse["juz_number"],
                "hizb_number" => $verse["hizb_number"],
                "rub_el_hizb_number" => $verse["rub_el_hizb_number"],
                "ruku_number" => $verse["ruku_number"],
                "manzil_number" => $verse["manzil_number"],
                "sajda_number" => $verse["sajda_number"],
                "page_number" => $verse["page_number"],
                "quran_verse_translation" => [
                    [
                        "lan" => "ar",
                        "text" => $verse["text_uthmani"],
                        "text_notashkeel" => $verse["text_imlaei_simple"],
                    ],
                    [
                        "lan" => "en",
                        "text" => $verse["translations"][0]["text"],
                        "text_notashkeel" => $verse["translations"][1]["text"],
                    ]
                ]

            ];
        }

        return $data;

    }

    private function quranClient(): HttpUtil
    {
        $accessToken = $this->getAccessToken();
        return HttpUtil::withBearerToken(
            config('services.quran.api_url'),
            $accessToken
        );
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
}
