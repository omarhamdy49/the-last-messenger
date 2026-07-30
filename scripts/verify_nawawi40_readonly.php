<?php

declare(strict_types=1);

use App\Domain\Hadith\Importers\HadithCollectionNormalizer;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceDir = '/Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/production/nawawi40';
$portalDir = '/Volumes/Data/projects/the-last-massenger/portal-data/nawawi40';
$tables = HadithCollectionNormalizer::TABLES;
$jsonColumns = [
    'hadith_collections' => ['name', 'description'],
    'hadith_books' => ['title', 'description'],
    'hadith_chapters' => ['name', 'description'],
    'hadith_scholars' => ['name', 'full_name', 'kunya', 'biography'],
    'hadith_themes' => ['name'],
    'hadith_narrators' => ['name', 'full_name', 'kunya', 'biography'],
    'hadith_related_verses' => ['relevance'],
    'hadith_applications' => ['category', 'description', 'example'],
    'hadith_fiqh_rulings' => ['title', 'ruling', 'details'],
    'hadith_sync_logs' => ['metadata', 'summary'],
];

$decode = static fn (string $path): array => json_decode(
    (string) file_get_contents($path),
    true,
    flags: JSON_THROW_ON_ERROR,
);
$localized = static fn (mixed $ar, mixed $en): array => array_filter(
    ['ar' => is_scalar($ar) && trim((string) $ar) !== '' ? (string) $ar : null,
        'en' => is_scalar($en) && trim((string) $en) !== '' ? (string) $en : null],
    static fn (?string $value): bool => $value !== null,
);
$personName = static fn (array $person): array => $localized(
    $person['name_ar'] ?? $person['narrator_ar'] ?? $person['arabic_name'] ?? null,
    $person['narrator_en'] ?? $person['name_en'] ?? $person['name'] ?? null,
);
$normalizePerson = static function (string $name): string {
    $normalized = Normalizer::normalize($name, Normalizer::FORM_C) ?: $name;
    $normalized = str_replace(["\u{2019}", "\u{02BC}", '`'], "'", $normalized);
    $normalized = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $normalized);
    $normalized = str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $normalized);
    $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized));
    $normalized = preg_replace(
        '/^(?:(?:al[\s-]?)?(?:imam|shaykh)|hafiz|allama)[\s-]+/iu',
        '',
        $normalized,
    ) ?? $normalized;
    $normalized = preg_replace('/^(?:الامام|الشيخ|الحافظ|العلامه)\s+/u', '', $normalized) ?? $normalized;
    $normalized = preg_replace("/\bal[\s-]+['’]?(?=\\p{L})/iu", 'al', $normalized) ?? $normalized;
    $normalized = preg_replace("/\bal['’](?=\\p{L})/iu", 'al', $normalized) ?? $normalized;

    return $normalized === 'ibn hajar alasqalani' ? 'ibn hajar' : $normalized;
};
$personKey = static fn (array $name): string => $normalizePerson($name['en'] ?? $name['ar']);
$splitNames = static function (array $name): array {
    $parts = array_map(
        static fn (string $person): array => preg_split('/\s+(?:and|و)\s+/iu', trim($person)) ?: [],
        $name,
    );
    $count = max(array_map('count', $parts));
    if ($count === 1) {
        return [$name];
    }
    $people = array_fill(0, $count, []);
    foreach ($parts as $locale => $localeParts) {
        if (count($localeParts) !== $count) {
            continue;
        }
        foreach ($localeParts as $index => $person) {
            $people[$index][$locale] = $person;
        }
    }

    return array_values(array_filter($people));
};
$canon = static function (mixed $value) use (&$canon): mixed {
    if (! is_array($value)) {
        return $value;
    }
    if (! array_is_list($value)) {
        ksort($value);
    }
    foreach ($value as &$item) {
        $item = $canon($item);
    }

    return $value;
};
$multiset = static function (array $values) use ($canon): array {
    $out = [];
    foreach ($values as $value) {
        $key = json_encode($canon($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $out[$key] = ($out[$key] ?? 0) + 1;
    }
    ksort($out);

    return $out;
};
$scalarLeaves = static function (mixed $payload) use (&$scalarLeaves): array {
    if (! is_array($payload)) {
        return is_scalar($payload) && trim((string) $payload) !== '' ? [(string) $payload] : [];
    }

    return array_merge(...array_values(array_map($scalarLeaves, $payload)));
};
$comparePortalValue = static function (mixed $expected, mixed $actual) use ($canon): bool {
    if (is_array($expected)) {
        if (is_string($actual)) {
            $actual = json_decode($actual, true, flags: JSON_THROW_ON_ERROR);
        }

        return $canon($expected) === $canon($actual);
    }
    if (is_bool($expected)) {
        return $expected === (bool) $actual;
    }
    if (is_int($expected)) {
        return (string) $expected === (string) $actual;
    }

    return $expected === $actual;
};
$gradeKey = static function (string $raw): string {
    $grade = mb_strtolower(trim($raw));

    return match (true) {
        in_array($grade, ['unknown', 'ungraded', 'not graded', 'n/a'], true),
        str_contains($grade, 'غير معروف') => 'unknown',
        str_starts_with($grade, 'hasan'), str_starts_with($grade, 'حسن') => 'hasan',
        str_starts_with($grade, 'sahih'), str_starts_with($grade, 'صحيح') => 'sahih',
        str_contains($grade, 'mawdu'), str_contains($grade, 'fabricat'),
        str_contains($grade, 'موضوع') => 'mawdu',
        str_contains($grade, 'daif'), str_contains($grade, "da'if"),
        str_contains($grade, 'weak'), str_contains($grade, 'ضعيف') => 'daif',
        str_contains($grade, 'hasan'), str_contains($grade, 'حسن') => 'hasan',
        str_contains($grade, 'sahih'), str_contains($grade, 'authentic'),
        str_contains($grade, 'صحيح') => 'sahih',
        default => 'INVALID',
    };
};
$scholarMatches = static function (?array $expected, ?array $actual) use ($personKey): bool {
    if ($expected === null) {
        return $actual === null;
    }
    if ($actual === null) {
        return false;
    }

    return $personKey($expected) === $personKey($actual);
};

DB::statement('SET SESSION TRANSACTION READ ONLY');
DB::beginTransaction();

try {
    $report = [
        'database' => [
            'name' => DB::connection()->getDatabaseName(),
            'host' => config('database.connections.mysql.host'),
            'port' => (string) config('database.connections.mysql.port'),
        ],
        'counts' => [],
        'source' => [],
        'portal' => ['hash_errors' => [], 'row_errors' => []],
        'mismatches' => [],
        'duplicates' => [],
        'correctness' => [],
        'fk_orphans' => [],
        'auto_increment' => [],
    ];

    foreach ($tables as $table) {
        $report['counts'][$table] = DB::table($table)->count();
    }

    $manifest = $decode($portalDir.'/manifest.json');
    $portalRows = [];
    foreach ($tables as $table) {
        $meta = $manifest['tables'][$table] ?? null;
        $path = $portalDir.'/'.($meta['file'] ?? '');
        if (! is_file($path) || hash_file('sha256', $path) !== ($meta['sha256'] ?? null)) {
            $report['portal']['hash_errors'][] = $table;

            continue;
        }
        $envelope = $decode($path);
        $portalRows[$table] = $envelope['rows'] ?? [];
        if (($envelope['table'] ?? null) !== $table
            || ($envelope['count'] ?? null) !== count($portalRows[$table])
            || ($meta['rows'] ?? null) !== count($portalRows[$table])) {
            $report['portal']['row_errors'][] = "{$table}: envelope/manifest count mismatch";
        }

        $columns = array_keys($portalRows[$table][0] ?? []);
        $dbRows = $columns === [] ? [] : DB::table($table)->get($columns)->map(
            static fn (object $row): array => (array) $row,
        )->all();
        $key = $table === 'hadith_hadith_themes'
            ? static fn (array $row): string => $row['hadith_id'].'|'.$row['hadith_theme_id']
            : static fn (array $row): string => (string) $row['id'];
        $expectedByKey = [];
        $actualByKey = [];
        foreach ($portalRows[$table] as $row) {
            $expectedByKey[$key($row)] = $row;
        }
        foreach ($dbRows as $row) {
            $actualByKey[$key($row)] = $row;
        }
        if (array_keys($expectedByKey) !== array_keys($actualByKey)) {
            $report['portal']['row_errors'][] = "{$table}: row keys differ";

            continue;
        }
        foreach ($expectedByKey as $rowKey => $expected) {
            foreach ($expected as $column => $value) {
                if (! $comparePortalValue($value, $actualByKey[$rowKey][$column] ?? null)) {
                    $report['portal']['row_errors'][] = "{$table}[{$rowKey}].{$column}";
                }
            }
        }
    }

    $files = glob($sourceDir.'/*.json') ?: [];
    sort($files, SORT_STRING);
    $sources = [];
    foreach ($files as $file) {
        $sources[basename($file)] = $decode($file);
    }
    $report['source']['files'] = count($sources);
    $report['source']['schemas'] = [
        'flat' => count(array_filter($sources, static fn (array $json): bool => ! isset($json['hadith']))),
        'nested' => count(array_filter($sources, static fn (array $json): bool => isset($json['hadith']))),
    ];

    $dbSources = DB::table('hadith_sources')->get()->keyBy('source_file');
    $dbHadiths = DB::table('hadith_hadiths')->get()->keyBy('id');
    $dbTranslations = DB::table('hadith_translations')
        ->select(['hadith_id', 'locale', 'text', 'search_text'])
        ->get()->groupBy('hadith_id');
    $dbThemes = DB::table('hadith_hadith_themes as ht')
        ->join('hadith_themes as t', 't.id', '=', 'ht.hadith_theme_id')
        ->get(['ht.hadith_id', 't.id', 't.slug', 't.name'])->groupBy('hadith_id');
    $dbKeywords = DB::table('hadith_keywords')->get()->groupBy('hadith_id');
    $dbIsnad = DB::table('hadith_isnad as i')
        ->join('hadith_narrators as n', 'n.id', '=', 'i.narrator_id')
        ->get(['i.hadith_id', 'i.position_in_chain', 'i.transmission_method', 'n.id as narrator_id', 'n.name'])
        ->groupBy('hadith_id');
    $dbGradings = DB::table('hadith_gradings as g')
        ->leftJoin('hadith_scholars as s', 's.id', '=', 'g.scholar_id')
        ->get(['g.*', 's.name as scholar_name'])->groupBy('hadith_id');
    $dbComments = DB::table('hadith_commentaries as c')
        ->leftJoin('hadith_scholars as s', 's.id', '=', 'c.scholar_id')
        ->get(['c.*', 's.name as scholar_name'])->groupBy('hadith_id');
    $dbVerses = DB::table('hadith_related_verses as r')
        ->join('quran_verses as q', 'q.id', '=', 'r.quran_verse_id')
        ->get(['r.*', 'q.surah_id', 'q.verse_number'])->groupBy('hadith_id');
    $dbApplications = DB::table('hadith_applications')->get()->groupBy('hadith_id');
    $dbFiqhRulings = DB::table('hadith_fiqh_rulings')->get()->groupBy('hadith_id');

    $expectedCounts = array_fill_keys(
        ['hadiths', 'translations', 'sources', 'narrator_chain', 'narrator_references',
            'narrator_unique', 'scholar_references', 'scholar_unique', 'gradings', 'themes',
            'theme_unique_pairs', 'theme_unique_ar', 'theme_unique_en', 'keywords',
            'commentaries', 'related_verses', 'applications', 'application_values',
            'fiqh_rulings', 'fiqh_values'],
        0,
    );
    $allThemePairs = [];
    $allThemeAr = [];
    $allThemeEn = [];
    $allNarratorIdentities = [];
    $allScholarIdentities = [];

    foreach ($sources as $file => $json) {
        $sourceFile = 'nawawi40/'.$file;
        $dbSource = $dbSources[$sourceFile] ?? null;
        if ($dbSource === null) {
            $report['mismatches'][] = "{$file}: no hadith_sources row";

            continue;
        }
        $hadithId = (int) $dbSource->hadith_id;
        $hadith = $dbHadiths[$hadithId] ?? null;
        if ($hadith === null) {
            $report['mismatches'][] = "{$file}: source points to missing hadith {$hadithId}";

            continue;
        }

        $collection = $json['collections'][0] ?? [];
        foreach ($json['collections'] ?? [] as $candidate) {
            if (($candidate['name_en'] ?? null) === ($json['metadata']['collection']['name_en'] ?? null)) {
                $collection = $candidate;
                break;
            }
        }
        $number = (string) ($collection['hadith_number']
            ?? $json['metadata']['sunnah_reference']['hadith_number']
            ?? $json['metadata']['id_in_book']
            ?? '');
        if ((string) $hadith->hadith_number !== $number) {
            $report['mismatches'][] = "{$file}: hadith_number source={$number} db={$hadith->hadith_number}";
        }
        $expectedCounts['hadiths']++;
        $expectedCounts['sources']++;

        $translations = $dbTranslations[$hadithId] ?? collect();
        foreach (['ar' => 'text', 'en' => 'text', 'ar_notashkeel' => 'search_text'] as $sourceKey => $column) {
            $locale = $sourceKey === 'ar_notashkeel' ? 'ar' : $sourceKey;
            $row = $translations->firstWhere('locale', $locale);
            $sourceText = (string) ($json['hadith_text'][$sourceKey] ?? '');
            $sqlHash = DB::table('hadith_translations')
                ->where('hadith_id', $hadithId)->where('locale', $locale)
                ->selectRaw("SHA2({$column}, 256) AS digest")->value('digest');
            if ($sqlHash !== hash('sha256', $sourceText)) {
                $report['mismatches'][] = "{$file}: {$sourceKey} SHA256 mismatch";
            }
            if ($row === null) {
                $report['mismatches'][] = "{$file}: missing {$locale} translation";
            }
        }
        if ($hadith->text_hash !== hash('sha256', (string) $json['hadith_text']['ar'])) {
            $report['mismatches'][] = "{$file}: hadith text_hash mismatch";
        }
        $expectedCounts['translations'] += 2;

        $expectedThemes = [];
        $ar = $json['themes_ar'] ?? [];
        $en = $json['themes_en'] ?? [];
        for ($i = 0; $i < max(count($ar), count($en)); $i++) {
            $expectedThemes[] = $localized($ar[$i] ?? null, $en[$i] ?? null);
        }
        $nestedByArabic = [];
        foreach ($json['hadith']['themes'] ?? [] as $theme) {
            $name = $localized(
                $theme['arabic'] ?? $theme['name_ar'] ?? null,
                $theme['theme'] ?? $theme['name_en'] ?? null,
            );
            if (! isset($name['ar'])) {
                $expectedThemes[] = $name;

                continue;
            }
            $nestedByArabic[$name['ar']][$name['en'] ?? ''] = $name['en'] ?? null;
        }
        foreach ($nestedByArabic as $arabic => $englishByName) {
            $english = array_values(array_filter($englishByName));
            if (count($english) > 1) {
                $expectedThemes[] = ['ar' => $arabic];
                foreach ($english as $englishTheme) {
                    $expectedThemes[] = ['en' => $englishTheme];
                }

                continue;
            }
            $expectedThemes[] = $localized($arabic, $english[0] ?? null);
        }
        $actualThemes = array_map(
            static fn (object $row): array => json_decode($row->name, true, flags: JSON_THROW_ON_ERROR),
            ($dbThemes[$hadithId] ?? collect())->all(),
        );
        if ($multiset($expectedThemes) !== $multiset($actualThemes)) {
            $report['mismatches'][] = "{$file}: theme names differ";
        }
        foreach ($expectedThemes as $theme) {
            $allThemePairs[] = $theme;
            if (isset($theme['ar'])) {
                $allThemeAr[] = $theme['ar'];
            }
            if (isset($theme['en'])) {
                $allThemeEn[] = $theme['en'];
            }
        }
        $expectedCounts['themes'] += count($expectedThemes);

        $expectedKeywords = [];
        $addExpectedKeyword = static function (string $token) use (&$expectedKeywords): void {
            $locale = preg_match('/[\x{0600}-\x{06FF}]/u', $token) === 1 ? 'ar' : 'en';
            $keyword = mb_strtolower(trim($token));
            $expectedKeywords["{$locale}|{$keyword}"] = ['locale' => $locale, 'keyword' => $keyword];
        };
        foreach ($json['keywords'] ?? [] as $keyword) {
            $addExpectedKeyword((string) $keyword);
        }
        foreach ($json['hadith']['keywords']['arabic'] ?? [] as $keyword) {
            $addExpectedKeyword((string) $keyword);
        }
        foreach ($json['hadith']['keywords']['english'] ?? [] as $keyword) {
            $addExpectedKeyword((string) $keyword);
        }
        foreach ($json['hadith']['tags'] ?? [] as $keyword) {
            $addExpectedKeyword((string) $keyword);
        }
        foreach ($json['hadith']['keywords']['concepts'] ?? [] as $keyword) {
            $addExpectedKeyword((string) $keyword);
        }
        $expectedKeywords = array_values($expectedKeywords);
        $actualKeywords = array_map(
            static fn (object $row): array => ['locale' => $row->locale, 'keyword' => $row->keyword],
            ($dbKeywords[$hadithId] ?? collect())->all(),
        );
        if ($multiset($expectedKeywords) !== $multiset($actualKeywords)) {
            $report['mismatches'][] = "{$file}: keywords differ";
        }
        $expectedCounts['keywords'] += count($expectedKeywords);

        $chain = $json['sanad']['narrators'] ?? $json['hadith']['sanad']['chain'] ?? [];
        $expectedChain = [];
        foreach ($chain as $entry) {
            $person = is_array($entry['narrator'] ?? null) ? $entry['narrator'] : $entry;
            $name = $personName($person);
            if ($name === []) {
                continue;
            }
            $position = (int) ($entry['order'] ?? $person['order'] ?? 0);
            $expectedChain[] = ['position' => $position, 'name' => $name];
            $allNarratorIdentities[] = $personKey($name);
        }
        $actualChain = array_map(
            static fn (object $row): array => [
                'position' => (int) $row->position_in_chain,
                'name' => json_decode($row->name, true, flags: JSON_THROW_ON_ERROR),
            ],
            ($dbIsnad[$hadithId] ?? collect())->all(),
        );
        if ($multiset($expectedChain) !== $multiset($actualChain)) {
            $report['mismatches'][] = "{$file}: isnad chain differs";
        }
        $expectedCounts['narrator_chain'] += count($expectedChain);
        $expectedCounts['narrator_references'] += count($expectedChain);
        if (is_array($json['companion'] ?? null) && ($name = $personName($json['companion'])) !== []) {
            $expectedCounts['narrator_references']++;
            $allNarratorIdentities[] = $personKey($name);
            if ($hadith->primary_narrator_id === null) {
                $report['mismatches'][] = "{$file}: populated companion not linked";
            }
        }

        $expectedGrades = [];
        $grading = $json['grading'] ?? [];
        if (is_array($grading) && is_scalar($grading['status'] ?? null)
            && trim((string) $grading['status']) !== '') {
            $expectedGrades[] = ['raw' => (string) $grading['status'], 'scholar' => null];
        }
        if (is_array($grading) && is_scalar($grading['albani_grade'] ?? null)
            && trim((string) $grading['albani_grade']) !== '') {
            $expectedGrades[] = [
                'raw' => (string) $grading['albani_grade'],
                'scholar' => ['ar' => 'الشيخ الألباني', 'en' => 'Shaykh al-Albani'],
            ];
        }
        $nestedGrading = $json['hadith']['grading'] ?? [];
        foreach (array_filter(
            array_merge(
                isset($nestedGrading['primary']) ? [$nestedGrading['primary']] : [],
                $nestedGrading['secondary'] ?? [],
            ),
            'is_array',
        ) as $item) {
            if (is_scalar($item['grade'] ?? null) && trim((string) $item['grade']) !== '') {
                $scholars = isset($item['scholar'])
                    ? $splitNames(['en' => (string) $item['scholar']])
                    : [null];
                foreach ($scholars as $scholar) {
                    $expectedGrades[] = ['raw' => (string) $item['grade'], 'scholar' => $scholar];
                }
            }
        }
        $actualGrades = array_map(
            static fn (object $row): array => [
                'raw' => $row->raw_grade,
                'scholar' => $row->scholar_name === null
                    ? null
                    : json_decode($row->scholar_name, true, flags: JSON_THROW_ON_ERROR),
            ],
            ($dbGradings[$hadithId] ?? collect())->all(),
        );
        $unmatchedGrades = $actualGrades;
        foreach ($expectedGrades as $expectedGrade) {
            $match = array_search(true, array_map(
                static fn (array $actualGrade): bool => $actualGrade['raw'] === $expectedGrade['raw']
                    && $scholarMatches($expectedGrade['scholar'], $actualGrade['scholar']),
                $unmatchedGrades,
            ), true);
            if ($match === false) {
                $unmatchedGrades[] = ['raw' => '__EXPECTED_NOT_FOUND__', 'scholar' => $expectedGrade['scholar']];

                continue;
            }
            unset($unmatchedGrades[$match]);
        }
        if ($unmatchedGrades !== []) {
            $report['mismatches'][] = "{$file}: gradings/raw scholar differ";
        }
        foreach ($expectedGrades as $grade) {
            if ($grade['scholar'] !== null) {
                $allScholarIdentities[] = $personKey($grade['scholar']);
                $expectedCounts['scholar_references']++;
            }
        }
        foreach ($dbGradings[$hadithId] ?? collect() as $row) {
            if ($row->grade_key !== $gradeKey($row->raw_grade)) {
                $report['mismatches'][] = "{$file}: raw_grade={$row->raw_grade} maps to {$row->grade_key}";
            }
        }
        $expectedCounts['gradings'] += count($expectedGrades);

        $expectedComments = [];
        foreach ($json['scholarly_comments'] ?? [] as $comment) {
            $names = $localized(
                $comment['scholar_ar'] ?? null,
                $comment['scholar_en'] ?? $comment['scholar'] ?? null,
            );
            foreach (['ar' => 'comment_ar', 'en' => 'comment_en'] as $locale => $field) {
                if (is_scalar($comment[$field] ?? null) && trim((string) $comment[$field]) !== '') {
                    $expectedComments[] = [
                        'locale' => $locale,
                        'text' => (string) $comment[$field],
                        'author' => $names[$locale] ?? $names['en'] ?? $names['ar'] ?? null,
                    ];
                }
            }
            if ($names !== []) {
                foreach ($splitNames($names) as $name) {
                    $allScholarIdentities[] = $personKey($name);
                    $expectedCounts['scholar_references']++;
                }
            }
        }
        foreach ($json['hadith']['scholarly_commentary'] ?? [] as $comment) {
            if (! is_scalar($comment['commentary'] ?? null) || trim((string) $comment['commentary']) === '') {
                continue;
            }
            $names = $localized($comment['arabic_name'] ?? null, $comment['scholar'] ?? null);
            $expectedComments[] = [
                'locale' => 'en',
                'text' => (string) $comment['commentary'],
                'author' => $names['en'] ?? $names['ar'] ?? null,
            ];
            if ($names !== []) {
                foreach ($splitNames($names) as $name) {
                    $allScholarIdentities[] = $personKey($name);
                    $expectedCounts['scholar_references']++;
                }
            }
        }
        $actualComments = array_map(
            static fn (object $row): array => [
                'locale' => $row->locale, 'text' => $row->text, 'author' => $row->author_name,
            ],
            ($dbComments[$hadithId] ?? collect())->all(),
        );
        if ($multiset($expectedComments) !== $multiset($actualComments)) {
            $report['mismatches'][] = "{$file}: commentaries differ";
        }
        $expectedCounts['commentaries'] += count($expectedComments);

        $expectedVerses = [];
        foreach ($json['related_verses'] ?? [] as $verse) {
            $ayahs = is_array($verse['ayah'] ?? null) ? $verse['ayah'] : [$verse['ayah'] ?? null];
            foreach ($ayahs as $ayah) {
                $numbers = is_numeric($ayah) ? [(int) $ayah] : [];
                if (is_string($ayah) && preg_match('/^(\d+)\s*-\s*(\d+)$/', trim($ayah), $m) === 1) {
                    $numbers = range((int) $m[1], (int) $m[2]);
                }
                foreach ($numbers as $number) {
                    $expectedVerses[] = [
                        'surah' => (int) $verse['surah'],
                        'ayah' => $number,
                        'relevance' => $localized(
                            $verse['relevance_ar'] ?? null,
                            $verse['relevance_en'] ?? $verse['relevance'] ?? null,
                        ) ?: null,
                    ];
                }
            }
        }
        $actualVerses = array_map(
            static fn (object $row): array => [
                'surah' => (int) $row->surah_id,
                'ayah' => (int) $row->verse_number,
                'relevance' => $row->relevance === null
                    ? null
                    : json_decode($row->relevance, true, flags: JSON_THROW_ON_ERROR),
            ],
            ($dbVerses[$hadithId] ?? collect())->all(),
        );
        if ($multiset($expectedVerses) !== $multiset($actualVerses)) {
            $report['mismatches'][] = "{$file}: related verses differ";
        }
        $expectedCounts['related_verses'] += count($expectedVerses);

        $sourceApplications = array_merge(
            array_values(array_filter($json['applications'] ?? [], 'is_array')),
            array_values(array_filter($json['hadith']['contemporary_applications'] ?? [], 'is_array')),
        );
        $applicationValues = $scalarLeaves($sourceApplications);
        $storedApplicationValues = [];
        foreach ($dbApplications[$hadithId] ?? collect() as $application) {
            foreach (['category', 'description', 'example'] as $column) {
                $storedApplicationValues = array_merge(
                    $storedApplicationValues,
                    $scalarLeaves($application->{$column} === null
                        ? null
                        : json_decode($application->{$column}, true, flags: JSON_THROW_ON_ERROR)),
                );
            }
        }
        if ($multiset($applicationValues) !== $multiset($storedApplicationValues)) {
            $report['mismatches'][] = "{$file}: application values differ";
        }
        $expectedCounts['applications'] += count($sourceApplications);
        $expectedCounts['application_values'] += count($applicationValues);

        $legal = $json['legal_rulings'] ?? [];
        $bidah = $json['types_of_bidah'] ?? [];
        $fiqh = $json['hadith']['fiqh_rulings'] ?? [];
        $fiqhPayloads = [
            $legal['fiqh_principles_ar'] ?? [],
            $legal['fiqh_principles_en'] ?? [],
            is_array($fiqh) && ! array_is_list($fiqh) ? $fiqh : [],
            $bidah['classifications_ar'] ?? [],
            $bidah['classifications_en'] ?? [],
        ];
        $fiqhValues = $scalarLeaves($fiqhPayloads);
        $storedFiqhValues = [];
        foreach ($dbFiqhRulings[$hadithId] ?? collect() as $ruling) {
            foreach (['title', 'ruling', 'details'] as $column) {
                $storedFiqhValues = array_merge(
                    $storedFiqhValues,
                    $scalarLeaves($ruling->{$column} === null
                        ? null
                        : json_decode($ruling->{$column}, true, flags: JSON_THROW_ON_ERROR)),
                );
            }
        }
        if ($multiset($fiqhValues) !== $multiset($storedFiqhValues)) {
            $report['mismatches'][] = "{$file}: fiqh values differ";
        }
        $expectedCounts['fiqh_rulings'] += max(
            count($legal['fiqh_principles_ar'] ?? []),
            count($legal['fiqh_principles_en'] ?? []),
        );
        if (is_array($fiqh) && ! array_is_list($fiqh)) {
            $expectedCounts['fiqh_rulings'] += isset($fiqh['primary_ruling']) ? 1 : 0;
            foreach (['general_principles', 'practical_applications', 'details', 'schools_of_thought'] as $field) {
                $expectedCounts['fiqh_rulings'] += count($fiqh[$field] ?? []);
            }
        }
        $expectedCounts['fiqh_rulings'] += max(
            count($bidah['classifications_ar'] ?? []),
            count($bidah['classifications_en'] ?? []),
        );
        $expectedCounts['fiqh_values'] += count($fiqhValues);
    }

    $expectedCounts['theme_unique_pairs'] = count($multiset($allThemePairs));
    $expectedCounts['theme_unique_ar'] = count(array_unique($allThemeAr));
    $expectedCounts['theme_unique_en'] = count(array_unique($allThemeEn));
    $expectedCounts['narrator_unique'] = count(array_unique($allNarratorIdentities));
    $expectedCounts['scholar_unique'] = count(array_unique($allScholarIdentities));
    $report['source']['expected_counts'] = $expectedCounts;

    $duplicateQueries = [
        'book_number' => 'SELECT collection_id, book_number, COUNT(*) n FROM hadith_books GROUP BY collection_id, book_number HAVING n > 1',
        'chapter_number' => 'SELECT book_id, chapter_number, COUNT(*) n FROM hadith_chapters GROUP BY book_id, chapter_number HAVING n > 1',
        'hadith_number' => 'SELECT hadith_number, COUNT(*) n FROM hadith_hadiths GROUP BY hadith_number HAVING n > 1',
        'text_hash' => 'SELECT text_hash, COUNT(*) n FROM hadith_hadiths GROUP BY text_hash HAVING n > 1',
        'translation_locale' => 'SELECT hadith_id, locale, COUNT(*) n FROM hadith_translations GROUP BY hadith_id, locale HAVING n > 1',
        'source_identity' => 'SELECT source_system, external_id, COUNT(*) n FROM hadith_sources GROUP BY source_system, external_id HAVING n > 1',
        'theme_name' => 'SELECT name, COUNT(*) n FROM hadith_themes GROUP BY name HAVING n > 1',
        'theme_link' => 'SELECT hadith_id, hadith_theme_id, COUNT(*) n FROM hadith_hadith_themes GROUP BY hadith_id, hadith_theme_id HAVING n > 1',
        'keyword' => 'SELECT hadith_id, locale, keyword, COUNT(*) n FROM hadith_keywords GROUP BY hadith_id, locale, keyword HAVING n > 1',
        'narrator_name' => 'SELECT name, COUNT(*) n FROM hadith_narrators GROUP BY name HAVING n > 1',
        'scholar_name' => 'SELECT name, COUNT(*) n FROM hadith_scholars GROUP BY name HAVING n > 1',
        'grading' => 'SELECT hadith_id, COALESCE(scholar_id, 0) scholar, grade_key, COUNT(*) n FROM hadith_gradings GROUP BY hadith_id, COALESCE(scholar_id, 0), grade_key HAVING n > 1',
        'isnad_position' => 'SELECT hadith_id, position_in_chain, COUNT(*) n FROM hadith_isnad GROUP BY hadith_id, position_in_chain HAVING n > 1',
        'commentary' => 'SELECT hadith_id, locale, COALESCE(scholar_id, 0), text, COUNT(*) n FROM hadith_commentaries GROUP BY hadith_id, locale, COALESCE(scholar_id, 0), text HAVING n > 1',
        'related_verse' => 'SELECT hadith_id, quran_verse_id, COUNT(*) n FROM hadith_related_verses GROUP BY hadith_id, quran_verse_id HAVING n > 1',
        'application' => 'SELECT hadith_id, category, description, example, COUNT(*) n FROM hadith_applications GROUP BY hadith_id, category, description, example HAVING n > 1',
        'fiqh_ruling' => 'SELECT hadith_id, type, title, ruling, details, COUNT(*) n FROM hadith_fiqh_rulings GROUP BY hadith_id, type, title, ruling, details HAVING n > 1',
    ];
    foreach ($duplicateQueries as $key => $sql) {
        $report['duplicates'][$key] = array_map(static fn (object $row): array => (array) $row, DB::select($sql));
    }

    $report['correctness']['invalid_grade_keys'] = DB::table('hadith_gradings')
        ->whereNotIn('grade_key', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'])->count();
    $report['correctness']['isnad_noncontiguous'] = array_values(array_filter(
        DB::table('hadith_isnad')->orderBy('hadith_id')->orderBy('position_in_chain')
            ->get(['hadith_id', 'position_in_chain'])->groupBy('hadith_id')->map(
                static fn ($rows, $hadithId): ?array => $rows->pluck('position_in_chain')->map(
                    static fn ($value): int => (int) $value,
                )->all() === range(1, $rows->count())
                    ? null
                    : ['hadith_id' => $hadithId, 'positions' => $rows->pluck('position_in_chain')->all()],
            )->all(),
    ));
    $lowerColumns = [
        'hadith_collections' => ['code', 'slug'],
        'hadith_translations' => ['locale'],
        'hadith_gradings' => ['grade_key', 'review_status'],
        'hadith_commentaries' => ['locale', 'review_status'],
        'hadith_themes' => ['slug', 'review_status'],
        'hadith_keywords' => ['locale', 'review_status'],
        'hadith_sources' => ['source_system', 'source_type', 'verification_method'],
        'hadith_related_verses' => ['review_status'],
        'hadith_applications' => ['review_status'],
        'hadith_fiqh_rulings' => ['type', 'review_status'],
        'hadith_sync_logs' => ['source_collection_code', 'type', 'status'],
    ];
    foreach ($lowerColumns as $table => $columns) {
        foreach ($columns as $column) {
            $report['correctness']['nonlowercase']["{$table}.{$column}"] = DB::table($table)
                ->whereNotNull($column)->whereRaw("{$column} <> LOWER({$column})")->count();
        }
    }

    $orphans = [
        'books.collection' => ['hadith_books b', 'hadith_collections p', 'p.id = b.collection_id'],
        'chapters.book' => ['hadith_chapters b', 'hadith_books p', 'p.id = b.book_id'],
        'hadiths.chapter' => ['hadith_hadiths b', 'hadith_chapters p', 'p.id = b.chapter_id'],
        'hadiths.primary_narrator' => ['hadith_hadiths b', 'hadith_narrators p', 'p.id = b.primary_narrator_id', 'b.primary_narrator_id IS NOT NULL'],
        'translations.hadith' => ['hadith_translations b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'sources.hadith' => ['hadith_sources b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'isnad.hadith' => ['hadith_isnad b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'isnad.narrator' => ['hadith_isnad b', 'hadith_narrators p', 'p.id = b.narrator_id'],
        'gradings.hadith' => ['hadith_gradings b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'gradings.scholar' => ['hadith_gradings b', 'hadith_scholars p', 'p.id = b.scholar_id', 'b.scholar_id IS NOT NULL'],
        'commentaries.hadith' => ['hadith_commentaries b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'commentaries.scholar' => ['hadith_commentaries b', 'hadith_scholars p', 'p.id = b.scholar_id', 'b.scholar_id IS NOT NULL'],
        'hadith_themes.hadith' => ['hadith_hadith_themes b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'hadith_themes.theme' => ['hadith_hadith_themes b', 'hadith_themes p', 'p.id = b.hadith_theme_id'],
        'keywords.hadith' => ['hadith_keywords b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'verses.hadith' => ['hadith_related_verses b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'verses.quran' => ['hadith_related_verses b', 'quran_verses p', 'p.id = b.quran_verse_id'],
        'applications.hadith' => ['hadith_applications b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'fiqh.hadith' => ['hadith_fiqh_rulings b', 'hadith_hadiths p', 'p.id = b.hadith_id'],
        'fiqh.scholar' => ['hadith_fiqh_rulings b', 'hadith_scholars p', 'p.id = b.scholar_id', 'b.scholar_id IS NOT NULL'],
        'sync.collection' => ['hadith_sync_logs b', 'hadith_collections p', 'p.id = b.collection_id', 'b.collection_id IS NOT NULL'],
    ];
    foreach ($orphans as $key => $parts) {
        [$child, $parent, $on, $where] = array_pad($parts, 4, '1=1');
        $report['fk_orphans'][$key] = (int) DB::selectOne(
            "SELECT COUNT(*) n FROM {$child} LEFT JOIN {$parent} ON {$on} WHERE {$where} AND p.id IS NULL",
        )->n;
    }

    foreach (DB::select(
        "SELECT TABLE_NAME, AUTO_INCREMENT FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'hadith\\_%' ORDER BY TABLE_NAME",
    ) as $row) {
        $report['auto_increment'][$row->TABLE_NAME] = $row->AUTO_INCREMENT;
    }

    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
} finally {
    DB::rollBack();
}
