<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Fills in reciter portraits and countries from Wikidata.
 *
 * The everyayah catalogue carries neither, and there is no public CDN of qari
 * photographs with usable licensing. Wikidata links to Wikimedia Commons, whose
 * files are freely licensed and served from a stable CDN, and it also holds
 * country of citizenship.
 *
 * Matching is by name, so it is deliberately conservative: a candidate is only
 * accepted when Wikidata says it is a human who is a qāriʾ or an imam. Attaching
 * a stranger's photograph to a reciter is far worse than leaving the field null,
 * so anything unproven is skipped and reported.
 *
 * Examples:
 *   php artisan quran:enrich-reciters
 *   php artisan quran:enrich-reciters --force        # re-resolve rows already filled
 *   php artisan quran:enrich-reciters --only=alafasy
 */
class EnrichQuranRecitersCommand extends Command
{
    protected $signature = 'quran:enrich-reciters
        {--only= : Comma-separated reciter slugs}
        {--force : Overwrite values that are already set}';

    protected $description = 'Fill reciter profile images and countries from Wikidata / Wikimedia Commons';

    private const API = 'https://www.wikidata.org/w/api.php';

    private const ENTITY = 'https://www.wikidata.org/wiki/Special:EntityData/';

    /** Set when Wikidata throttled us, so the summary can say so plainly. */
    private bool $rateLimited = false;

    /** Wikidata occupations that confirm a candidate really is a Quran reciter. */
    private const OCCUPATIONS = [
        'Q3595924', // qāriʾ — reciter of the Quran
        'Q125482',  // imam
    ];

    /**
     * Search terms for reciters whose catalogue name does not find them.
     * Keyed by reciter slug.
     *
     * @var array<string, string>
     */
    private const SEARCH_ALIASES = [
        'alafasy' => 'Mishary Rashid Alafasy',
        'ghamadi' => 'Saad Al-Ghamdi',
        'husary' => 'Mahmoud Khalil Al-Hussary',
        'husary-mujawwad' => 'Mahmoud Khalil Al-Hussary',
        'husary-muallim' => 'Mahmoud Khalil Al-Hussary',
        'menshawi' => 'Mohamed Siddiq El-Minshawi',
        'minshawy-mujawwad' => 'Mohamed Siddiq El-Minshawi',
        'minshawy-murattal' => 'Mohamed Siddiq El-Minshawi',
        'abdul-basit-murattal' => 'Abdul Basit Abdus Samad',
        'abdul-basit-mujawwad' => 'Abdul Basit Abdus Samad',
        'abdulsamad-quranexplorercom' => 'Abdul Basit Abdus Samad',
        'warsh-abdul-basit' => 'Abdul Basit Abdus Samad',
        'saood-bin-ibraaheem-ash-shuraym' => 'Saud Al-Shuraim',
        'abdurrahmaan-as-sudais' => 'Abdul Rahman Al-Sudais',
        'abu-bakr-ash-shaatree' => 'Abu Bakr al-Shatri',
        'hudhaify' => 'Ali Al-Hudhaify',
        'hani-rifai' => 'Hani ar-Rifai',
        'mohammad-al-tablaway' => 'Mohamed Mahmoud Al-Tablawi',
        'muhsin-al-qasim' => 'Abdul Muhsin Al-Qasim',
        'abdullaah-3awwaad-al-juhaynee' => 'Abdullah Awad Al-Juhani',
        'yasser-ad-dussary' => 'Yasser Al-Dosari',
        'nasser-alqatami' => 'Nasser Al Qatami',
        'ahmed-ibn-ali-al-ajamy' => 'Ahmad bin Ali Al-Ajmi',
        'ahmed-ibn-ali-al-ajamy-ketaballahnet' => 'Ahmad bin Ali Al-Ajmi',
        'ahmed-ibn-ali-al-ajamy-quranexplorercom' => 'Ahmad bin Ali Al-Ajmi',
        'maher-al-muaiqly' => 'Maher Al Mueaqly',
        'salaah-abdulrahman-bukhatir' => 'Salah Bukhatir',
        'mahmoud-ali-al-banna' => 'Mahmoud Ali Al-Banna',
        'mustafa-ismail' => 'Mustafa Ismail',
        'ibrahim-akhdar' => 'Ibrahim Al-Akhdar',
        'muhammad-ayyoub' => 'Muhammad Ayyub',
        'muhammad-jibreel' => 'Muhammad Jibreel',
        'ali-jaber' => 'Ali Abdullah Jaber',
        'fares-abbad' => 'Fares Abbad',
        'ayman-sowaid' => 'Ayman Suwaid',
        'abdullah-basfar' => 'Abdullah Basfar',
        'abdullah-matroud' => 'Abdullah Al-Matroud',
        'salah-al-budair' => 'Salah Al-Budair',
    ];

    public function handle(): int
    {
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));
        $force = (bool) $this->option('force');

        $reciters = Reciter::query()->recitationsOnly()->orderBy('id')->get();
        if ($reciters->isEmpty()) {
            $this->error('No reciters found — run quran:import-reciters first.');

            return self::FAILURE;
        }

        $matched = 0;
        $images = 0;
        $countries = 0;
        $unmatched = [];

        foreach ($reciters as $reciter) {
            if ($only && ! in_array($reciter->slug, $only, true)) {
                continue;
            }

            if (! $force && $reciter->profile_image && $reciter->country) {
                continue;
            }

            $term = self::SEARCH_ALIASES[$reciter->slug] ?? $reciter->getDisplayName('en');
            $entity = $this->resolve($term);

            if ($entity === null) {
                $unmatched[] = $reciter->slug;

                continue;
            }

            $matched++;
            $dirty = false;

            if (($force || ! $reciter->profile_image) && $entity['image'] !== null) {
                $reciter->profile_image = $entity['image'];
                $images++;
                $dirty = true;
            }

            if (($force || ! $reciter->country) && $entity['country'] !== null) {
                $reciter->country = $entity['country'];
                $countries++;
                $dirty = true;
            }

            if ($dirty) {
                $reciter->save();
            }

            $this->line("→ {$reciter->slug} ({$entity['qid']})".
                ($entity['image'] ? ' image' : '').
                ($entity['country'] ? ' '.$entity['country'] : ''));
        }

        QuranCacheKeys::flushReciters();

        $this->newLine();
        $this->info("Matched {$matched} reciters on Wikidata — set {$images} portraits, {$countries} countries.");

        if ($unmatched !== []) {
            $this->warn(count($unmatched).' had no confident match (left blank): '.implode(', ', $unmatched));
            $this->line('Add a search term to SEARCH_ALIASES for any that should have matched.');
        }

        if ($this->rateLimited) {
            $this->warn('Wikidata rate limited part of this run — re-run to resolve the reciters it skipped.');
        }

        return self::SUCCESS;
    }

    /**
     * Find a Wikidata entity for a name and return its portrait + country, but
     * only if the entity is verifiably a reciter.
     *
     * @return array{qid: string, image: ?string, country: ?string}|null
     */
    private function resolve(string $term): ?array
    {
        foreach ($this->search($term) as $qid) {
            $entity = $this->entity($qid);
            if ($entity === null || ! $this->isReciter($entity)) {
                continue;
            }

            return [
                'qid' => $qid,
                'image' => $this->commonsUrl($this->claim($entity, 'P18')),
                'country' => $this->isoCode($this->claim($entity, 'P27')),
            ];
        }

        return null;
    }

    /**
     * Candidate QIDs, most promising first.
     *
     * Search results carry a description, which is used to rank candidates
     * before any are fetched — every entity fetch is a request against a rate
     * limited API, so ordering here directly cuts how many are needed.
     *
     * @return array<int, string>
     */
    private function search(string $term): array
    {
        $response = $this->get(self::API, [
            'action' => 'wbsearchentities',
            'search' => $term,
            'language' => 'en',
            'uselang' => 'en',
            'type' => 'item',
            'limit' => 5,
            'format' => 'json',
        ]);

        if ($response === null) {
            return [];
        }

        $hits = [];
        foreach ($response['search'] ?? [] as $hit) {
            if (! isset($hit['id'])) {
                continue;
            }
            $blurb = mb_strtolower(($hit['description'] ?? '').' '.($hit['label'] ?? ''));
            $looksRight = preg_match('/qur|recit|imam|qari|قارئ|إمام/u', $blurb) === 1;
            $hits[] = ['id' => $hit['id'], 'rank' => $looksRight ? 0 : 1];
        }

        usort($hits, static fn (array $a, array $b): int => $a['rank'] <=> $b['rank']);

        return array_column(array_slice($hits, 0, 3), 'id');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function entity(string $qid): ?array
    {
        // Cached for a day so a re-run (or --force) is mostly local; the same
        // country entity is also resolved for many reciters.
        return Cache::remember("wikidata:entity:{$qid}", 86400, function () use ($qid): ?array {
            $payload = $this->get(self::ENTITY.$qid.'.json');

            return $payload['entities'][$qid] ?? null;
        });
    }

    /**
     * A throttled GET that treats 429 as "wait", not "not found".
     *
     * Wikimedia rate limits anonymous clients aggressively. Without this, a run
     * across the whole catalogue silently degrades into blank results that look
     * exactly like genuinely unmatched reciters.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    private function get(string $url, array $query = []): ?array
    {
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            // Stay well inside the anonymous request budget.
            usleep(900_000);

            try {
                $response = $this->http()->get($url, $query);
            } catch (Throwable) {
                // A dropped connection is throttling by another name — record it
                // so the run does not report a transient failure as "no match".
                $this->rateLimited = true;
                sleep($attempt);

                continue;
            }

            if ($response->ok()) {
                return $response->json() ?? null;
            }

            // 404 is a real answer. Everything else here — 429, but also the 403
            // and Varnish 503 Wikimedia serves under load — means "try again";
            // treating those as "no match" is what silently blanked reciters
            // whose data resolves fine in isolation.
            if ($response->status() === 404) {
                return null;
            }

            $this->rateLimited = true;
            sleep(2 * $attempt);
        }

        return null;
    }

    /**
     * A human who is a qāriʾ or an imam. Without this gate a name search would
     * happily return a footballer who shares a reciter's name.
     *
     * @param  array<string, mixed>  $entity
     */
    private function isReciter(array $entity): bool
    {
        $ids = static fn (array $claims): array => array_values(array_filter(array_map(
            static fn (array $c) => $c['mainsnak']['datavalue']['value']['id'] ?? null,
            $claims,
        )));

        if (! in_array('Q5', $ids($entity['claims']['P31'] ?? []), true)) {
            return false;
        }

        return array_intersect(self::OCCUPATIONS, $ids($entity['claims']['P106'] ?? [])) !== [];
    }

    /**
     * @param  array<string, mixed>  $entity
     */
    private function claim(array $entity, string $property): string|array|null
    {
        return $entity['claims'][$property][0]['mainsnak']['datavalue']['value'] ?? null;
    }

    /**
     * Special:FilePath resolves a Commons filename to the CDN copy and keeps
     * working if the file is later moved. width caps the payload.
     */
    private function commonsUrl(string|array|null $filename): ?string
    {
        if (! is_string($filename) || $filename === '') {
            return null;
        }

        return 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode($filename).'?width=512';
    }

    /**
     * Country of citizenship is an entity reference; the reciters table stores
     * an ISO 3166-1 alpha-2 code, which lives on that entity as P297.
     */
    private function isoCode(string|array|null $country): ?string
    {
        $qid = is_array($country) ? ($country['id'] ?? null) : null;
        if ($qid === null) {
            return null;
        }

        $entity = $this->entity($qid);
        $code = $entity === null ? null : $this->claim($entity, 'P297');

        return is_string($code) ? $code : null;
    }

    private function http(): PendingRequest
    {
        // Wikimedia asks API clients to identify themselves.
        return Http::timeout(25)
            ->retry(2, 700)
            ->withHeaders(['User-Agent' => 'TheLastMessenger/1.0 (Quran reciter metadata)']);
    }
}
