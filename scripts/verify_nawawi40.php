<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceDir = '/Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/production/nawawi40';
$portalDir = '/Volumes/Data/projects/the-last-massenger/portal-data/nawawi40';
$jsonFlags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

function jsonFile(string $path): array
{
    return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

function canonical(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }
    if (! array_is_list($value)) {
        ksort($value);
    }
    foreach ($value as $key => $item) {
        $value[$key] = canonical($item);
    }

    return $value;
}

function signature(mixed $value): string
{
    return json_encode(canonical($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function localized(mixed $ar, mixed $en): array
{
    return array_filter(
        ['ar' => is_scalar($ar) && trim((string) $ar) !== '' ? (string) $ar : null,
            'en' => is_scalar($en) && trim((string) $en) !== '' ? (string) $en : null],
        static fn ($value): bool => $value !== null,
    );
}

function sourceNumber(array $json): string
{
    return (string) ($json['collections'][0]['hadith_number']
        ?? $json['metadata']['sunnah_reference']['hadith_number']
        ?? $json['metadata']['id_in_book']
        ?? '');
}

function themeRows(array $json): array
{
    $rows = [];
    $ar = $json['themes_ar'] ?? [];
    $en = $json['themes_en'] ?? [];
    for ($i = 0; $i < max(count($ar), count($en)); $i++) {
        $name = localized($ar[$i] ?? null, $en[$i] ?? null);
        if ($name !== []) {
            $rows[] = $name;
        }
    }
    foreach ($json['hadith']['themes'] ?? [] as $theme) {
        $name = localized($theme['arabic'] ?? $theme['name_ar'] ?? null, $theme['theme'] ?? $theme['name_en'] ?? null);
        if ($name !== []) {
            $rows[] = $name;
        }
    }

    return $rows;
}

function keywordRows(array $json): array
{
    $rows = [];
    foreach ($json['keywords'] ?? [] as $keyword) {
        if (is_scalar($keyword)) {
            $value = (string) $keyword;
            $rows[] = [preg_match('/[\x{0600}-\x{06FF}]/u', $value) === 1 ? 'ar' : 'en', $value];
        }
    }
    foreach ($json['hadith']['keywords']['arabic'] ?? [] as $keyword) {
        $rows[] = ['ar', (string) $keyword];
    }
    foreach ($json['hadith']['keywords']['english'] ?? [] as $keyword) {
        $rows[] = ['en', (string) $keyword];
    }

    return array_values(array_filter($rows, static fn (array $row): bool => trim($row[1]) !== ''));
}

function personName(array $person): array
{
    return localized(
        $person['name_ar'] ?? $person['narrator_ar'] ?? $person['arabic_name'] ?? null,
        $person['narrator_en'] ?? $person['name_en'] ?? $person['name'] ?? null,
    );
}

function personIdentity(array $name): string
{
    return mb_strtolower(trim(($name['ar'] ?? '').'|'.($name['en'] ?? '')));
}

function scholarIdentity(array $name): string
{
    $identity = personIdentity($name);

    return str_contains($identity, 'albani') || str_contains($identity, 'الألباني') ? 'al-albani' : $identity;
}

function gradeKey(string $raw): string
{
    $grade = mb_strtolower(trim($raw));

    return match (true) {
        $grade === 'unknown', $grade === 'ungraded', $grade === 'not graded', $grade === 'n/a',
        str_contains($grade, 'غير معروف') => 'unknown',
        str_starts_with($grade, 'hasan'), str_starts_with($grade, 'حسن') => 'hasan',
        str_starts_with($grade, 'sahih'), str_starts_with($grade, 'صحيح') => 'sahih',
        str_contains($grade, 'mawdu'), str_contains($grade, 'fabricat'), str_contains($grade, 'موضوع') => 'mawdu',
        str_contains($grade, 'daif'), str_contains($grade, "da'if"), str_contains($grade, 'weak'),
        str_contains($grade, 'ضعيف') => 'daif',
        str_contains($grade, 'hasan'), str_contains($grade, 'حسن') => 'hasan',
        str_contains($grade, 'sahih'), str_contains($grade, 'authentic'), str_contains($grade, 'صحيح') => 'sahih',
        default => 'INVALID',
    };
}

function gradingRows(array $json): array
{
    $rows = [];
    $grading = $json['grading'] ?? null;
    if (is_array($grading) && is_scalar($grading['status'] ?? null) && trim((string) $grading['status']) !== '') {
        $rows[] = ['raw' => (string) $grading['status'], 'scholar' => '', 'grade_key' => gradeKey((string) $grading['status'])];
    }
    if (is_array($grading) && is_scalar($grading['albani_grade'] ?? null) && trim((string) $grading['albani_grade']) !== '') {
        $raw = (string) $grading['albani_grade'];
        $rows[] = ['raw' => $raw, 'scholar' => 'al-albani', 'grade_key' => gradeKey($raw)];
    }
    $nested = $json['hadith']['grading'] ?? [];
    $candidates = [];
    if (is_array($nested['primary'] ?? null)) {
        $candidates[] = $nested['primary'];
    }
    foreach ($nested['secondary'] ?? [] as $row) {
        if (is_array($row)) {
            $candidates[] = $row;
        }
    }
    foreach ($candidates as $row) {
        if (! is_scalar($row['grade'] ?? null) || trim((string) $row['grade']) === '') {
            continue;
        }
        $raw = (string) $row['grade'];
        $name = localized(null, $row['scholar'] ?? null);
        $rows[] = ['raw' => $raw, 'scholar' => $name === [] ? '' : scholarIdentity($name), 'grade_key' => gradeKey($raw)];
    }

    return $rows;
}

function commentaryRows(array $json): array
{
    $rows = [];
    foreach ($json['scholarly_comments'] ?? [] as $comment) {
        if (! is_array($comment)) {
            continue;
        }
        $name = localized($comment['scholar_ar'] ?? null, $comment['scholar_en'] ?? $comment['scholar'] ?? null);
        foreach (['ar' => 'comment_ar', 'en' => 'comment_en'] as $locale => $key) {
            if (is_scalar($comment[$key] ?? null) && trim((string) $comment[$key]) !== '') {
                $rows[] = ['locale' => $locale, 'text' => (string) $comment[$key], 'scholar' => $name === [] ? '' : scholarIdentity($name)];
            }
        }
    }
    foreach ($json['hadith']['scholarly_commentary'] ?? [] as $comment) {
        if (! is_array($comment) || ! is_scalar($comment['commentary'] ?? null) || trim((string) $comment['commentary']) === '') {
            continue;
        }
        $name = localized($comment['arabic_name'] ?? null, $comment['scholar'] ?? null);
        $rows[] = ['locale' => 'en', 'text' => (string) $comment['commentary'], 'scholar' => $name === [] ? '' : scholarIdentity($name)];
    }

    return $rows;
}

function ayahs(mixed $value): array
{
    if (is_array($value)) {
        return array_values(array_unique(array_merge(...array_map('ayahs', $value))));
    }
    if (is_numeric($value)) {
        return [(int) $value];
    }
    if (is_string($value) && preg_match('/^(\d+)\s*-\s*(\d+)$/', trim($value), $match) === 1) {
        return range((int) $match[1], (int) $match[2]);
    }

    return [];
}

function relatedRows(array $json): array
{
    $rows = [];
    foreach ($json['related_verses'] ?? [] as $verse) {
        foreach (ayahs($verse['ayah'] ?? null) as $ayah) {
            $relevance = localized($verse['relevance_ar'] ?? null, $verse['relevance_en'] ?? $verse['relevance'] ?? null);
            $rows[] = ['coordinate' => ((int) ($verse['surah'] ?? 0)).":{$ayah}", 'relevance' => $relevance === [] ? null : $relevance];
        }
    }

    return $rows;
}

function recordPath(array &$inventory, string $path, mixed $value, string $file): void
{
    $inventory[$path]['present'] = ($inventory[$path]['present'] ?? 0) + 1;
    $inventory[$path]['types'][get_debug_type($value)] = ($inventory[$path]['types'][get_debug_type($value)] ?? 0) + 1;
    if ($value !== null && $value !== '' && $value !== []) {
        $inventory[$path]['populated'] = ($inventory[$path]['populated'] ?? 0) + 1;
        $inventory[$path]['files'][$file] = true;
    }
}

function walkPaths(array &$inventory, mixed $value, string $path, string $file): void
{
    if (! is_array($value)) {
        return;
    }
    foreach ($value as $key => $item) {
        $child = $path === '' ? (string) $key : "{$path}.{$key}";
        recordPath($inventory, $child, $item, $file);
        if (is_array($item)) {
            if (array_is_list($item)) {
                foreach ($item as $entry) {
                    recordPath($inventory, "{$child}.[]", $entry, $file);
                    walkPaths($inventory, $entry, "{$child}.[]", $file);
                }
            } else {
                walkPaths($inventory, $item, $child, $file);
            }
        }
    }
}

function sortedSignatures(array $rows): array
{
    $rows = array_map('signature', $rows);
    sort($rows, SORT_STRING);

    return $rows;
}

function normalizeDbRow(array $db, array $expected, array $jsonColumns): array
{
    foreach (array_keys($db) as $key) {
        if (! array_key_exists($key, $expected)) {
            unset($db[$key]);
        }
    }
    foreach ($expected as $key => $value) {
        if (in_array($key, $jsonColumns, true) && is_string($db[$key] ?? null)) {
            $db[$key] = json_decode($db[$key], true, flags: JSON_THROW_ON_ERROR);
        } elseif (is_bool($value) && $db[$key] !== null) {
            $db[$key] = (bool) $db[$key];
        } elseif (is_int($value) && $db[$key] !== null) {
            $db[$key] = (int) $db[$key];
        }
    }

    return canonical($db);
}

function sourceDisposition(string $path): array
{
    $mapped = [
        '/^collections\.\[\]\.(book_name_ar|book_name_en|chapter_name_ar|chapter_name_en|chapter_number|hadith_number|name_ar|name_en)$/',
        '/^companion\.(bio_ar|bio_en|birth_year_hijri|death_year_hijri|full_name_ar|name_ar|name_en)$/',
        '/^grading\.(albani_grade|notes_ar|notes_en|status|verified)$/',
        '/^hadith\.book\.(arabic_name|number)$/',
        '/^hadith\.chapter\.(arabic_title|number|title)$/',
        '/^hadith\.collection\.(arabic_name|collector|name)$/',
        '/^hadith\.grading\.primary\.(arabic|explanation|grade|scholar)$/',
        '/^hadith\.grading\.secondary\.\[\]\.(arabic|explanation|grade|reference|scholar|source)$/',
        '/^hadith\.sanad\.chain\.\[\]\.narrator\.(arabic_full_name|arabic_kunya|arabic_name|biographical_notes|birth_year|death_year|full_name|kunya|name|reliability)$/',
        '/^hadith\.sanad\.chain\.\[\]\.(order|transmission_verb)$/',
        '/^hadith\.scholarly_commentary\.\[\]\.(arabic_name|commentary|scholar|source)$/',
        '/^hadith\.themes\.\[\]\.(arabic|theme)$/',
        '/^hadith_text\.(ar|ar_notashkeel|en)$/',
        '/^metadata\.collection\.(author_en|name_ar|name_en)$/',
        '/^metadata\.(id|id_in_book)$/',
        '/^metadata\.sunnah_reference\.(collection|hadith_number)$/',
        '/^related_verses\.\[\]\.(ayah|relevance|surah)$/',
        '/^sanad\.narrators\.\[\]\.(bio_ar|bio_en|death_year_hijri|full_name_ar|name_ar|narrator_en|order|reliability_en)$/',
        '/^scholarly_comments\.\[\]\.(comment_ar|comment_en|scholar_ar|scholar_en|source|verified)$/',
        '/^source_verification\.sunnah\.(collection|hadith_number|status|url)$/',
        '/^(themes_ar|themes_en)\.\[\]$/',
    ];
    foreach ($mapped as $pattern) {
        if (preg_match($pattern, $path) === 1) {
            return ['mapped', 'mapped_to_current_schema'];
        }
    }
    if (in_array($path, ['keywords.[]', 'hadith.keywords.arabic.[]', 'hadith.keywords.english.[]'], true)) {
        return ['normalized', 'keyword_content_lowercased'];
    }
    if (
        in_array($path, [
            'hadith.collection.total_hadiths',
            'chapter_info',
            'collections.[].source_verified',
            'collections.[].verified',
            'metadata.collection.total_hadiths',
            'hadith.hadith_number.in_book',
            'hadith.hadith_number.in_chapter',
            'hadith.hadith_number.overall',
            'hadith.metadata.position_in_collection',
            'source_verification.sunnah.api_slug',
            'source_verification.sunnah.book_number',
            'verification_status',
        ], true)
    ) {
        return ['safe_redundant', 'derivable_or_duplicate_of_mapped_value'];
    }
    if (in_array($path, [
        'collections.[].source_url',
        'metadata.sunnah_reference.url',
        'metadata.chapter_id',
        'related_verses.[].surah_name_ar',
        'related_verses.[].surah_name_en',
        'related_verses.[].text_ar',
        'related_verses.[].text_en',
    ], true)) {
        return ['partial', 'some_values_shadowed_or_not_identical'];
    }

    $groups = [
        'alternate_hadith_content' => ['/^hadith\.matn\./', '/^hadith_text_alternate\./'],
        'applications' => ['/^applications\./', '/^hadith\.contemporary_applications\./'],
        'jurisprudence' => ['/^legal_rulings\./', '/^hadith\.fiqh_rulings\./', '/^types_of_bidah\./'],
        'context_significance_lessons' => ['/^historical_context\./', '/^significance_(ar|en)$/', '/^hadith\.historical_context\./', '/^hadith\.spiritual_lessons\./'],
        'grading_provenance_analysis' => ['/^grading\.(agreement|authenticated_by\.)/', '/^hadith\.grading\./'],
        'taxonomy_metadata' => ['/^hadith\.keywords\.concepts\./', '/^hadith\.tags\./', '/^hadith\.themes\.\[\]\.relevance$/'],
        'linguistic_analysis' => ['/^hadith\.linguistic_analysis\./'],
        'narrator_chain_metadata' => ['/^sanad\./', '/^hadith\.sanad\./', '/^companion\./'],
        'commentary_metadata' => ['/^hadith\.scholarly_commentary\.\[\]\.key_point$/'],
        'cross_references_metadata' => ['/^hadith\.related_hadiths\./', '/^hadith\.metadata\./'],
        'source_collection_metadata' => [
            '/^hadith\.book\.name$/',
            '/^metadata\.book_id$/',
            '/^metadata\.collection\.(author_ar|id)$/',
        ],
    ];
    foreach ($groups as $group => $patterns) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return ['dropped', $group];
            }
        }
    }

    return ['unclassified', 'unclassified'];
}

$sourceFiles = glob($sourceDir.'/*.json') ?: [];
sort($sourceFiles, SORT_STRING);
$sources = [];
$inventory = [];
foreach ($sourceFiles as $path) {
    $json = jsonFile($path);
    $file = basename($path);
    walkPaths($inventory, $json, '', $file);
    $sources[$file] = ['json' => $json, 'number' => sourceNumber($json)];
}
ksort($inventory);
foreach ($inventory as &$pathInfo) {
    $pathInfo['present'] ??= 0;
    $pathInfo['populated'] ??= 0;
    $pathInfo['files'] = array_keys($pathInfo['files'] ?? []);
    sort($pathInfo['files'], SORT_STRING);
    ksort($pathInfo['types']);
}
unset($pathInfo);

$pathDispositions = $dispositionTotals = [];
foreach ($inventory as $path => $info) {
    $hasLeaf = ! isset($info['types']['array']);
    if (! $hasLeaf || $info['populated'] === 0) {
        continue;
    }
    [$status, $group] = sourceDisposition($path);
    $pathDispositions[$path] = ['status' => $status, 'group' => $group, 'values' => $info['populated'], 'files' => count($info['files'])];
    $dispositionTotals[$status][$group] = ($dispositionTotals[$status][$group] ?? 0) + $info['populated'];
}

$manifest = jsonFile($portalDir.'/manifest.json');
$portal = [];
$portalIntegrity = [];
foreach ($manifest['tables'] as $table => $meta) {
    $path = $portalDir.'/'.$meta['file'];
    $envelope = jsonFile($path);
    $portal[$table] = $envelope['rows'];
    $portalIntegrity[$table] = [
        'manifest_rows' => $meta['rows'],
        'envelope_rows' => count($envelope['rows']),
        'checksum_match' => hash_file('sha256', $path) === $meta['sha256'],
        'envelope_table_match' => ($envelope['table'] ?? null) === $table,
    ];
}

$pdo = DB::connection()->getPdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('SET SESSION TRANSACTION READ ONLY');
$pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');

$tableNames = array_keys($manifest['tables']);
$startCounts = [];
foreach ($tableNames as $table) {
    $startCounts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}

$dbRows = [];
foreach ($tableNames as $table) {
    $dbRows[$table] = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
}

$jsonColumns = [
    'hadith_collections' => ['name', 'description'],
    'hadith_books' => ['title', 'description'],
    'hadith_chapters' => ['name', 'description'],
    'hadith_scholars' => ['name', 'full_name', 'kunya', 'biography'],
    'hadith_themes' => ['name'],
    'hadith_narrators' => ['name', 'full_name', 'kunya', 'biography'],
    'hadith_related_verses' => ['relevance'],
    'hadith_sync_logs' => ['metadata', 'summary'],
];
$portalComparison = [];
foreach ($portal as $table => $expectedRows) {
    $key = $table === 'hadith_hadith_themes'
        ? static fn (array $row): string => $row['hadith_id'].'|'.$row['hadith_theme_id']
        : static fn (array $row): string => (string) $row['id'];
    $actual = [];
    foreach ($dbRows[$table] as $row) {
        $actual[$key($row)] = $row;
    }
    $missing = $mismatch = [];
    foreach ($expectedRows as $expected) {
        $id = $key($expected);
        if (! isset($actual[$id])) {
            $missing[] = $id;

            continue;
        }
        $normalized = normalizeDbRow($actual[$id], $expected, $jsonColumns[$table] ?? []);
        if (signature($normalized) !== signature($expected)) {
            $mismatch[] = ['key' => $id, 'portal' => canonical($expected), 'db' => $normalized];
        }
        unset($actual[$id]);
    }
    $portalComparison[$table] = ['missing_in_db' => $missing, 'extra_in_db' => array_keys($actual), 'value_mismatches' => $mismatch];
}

$hadithByNumber = [];
foreach ($dbRows['hadith_hadiths'] as $row) {
    $hadithByNumber[(string) $row['hadith_number']][] = $row;
}
$sourceByHadith = [];
foreach ($dbRows['hadith_sources'] as $row) {
    $sourceByHadith[(string) $row['hadith_id']][] = $row;
}

$translationHashes = $pdo->query(
    'SELECT h.hadith_number,t.hadith_id,t.locale,SHA2(t.text,256) text_sha256,'
    .'SHA2(t.search_text,256) search_sha256 FROM hadith_translations t '
    .'JOIN hadith_hadiths h ON h.id=t.hadith_id'
)->fetchAll(PDO::FETCH_ASSOC);
$translationByNumber = [];
foreach ($translationHashes as $row) {
    $translationByNumber[(string) $row['hadith_number']][(string) $row['locale']] = $row;
}

$chapterByNumber = [];
foreach ($pdo->query(
    'SELECT h.hadith_number,c.chapter_number FROM hadith_hadiths h JOIN hadith_chapters c ON c.id=h.chapter_id'
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $chapterByNumber[(string) $row['hadith_number']] = (string) $row['chapter_number'];
}

$fileMatches = $textChecks = [];
$partialLosses = [
    'alternate_hadith_content' => [],
    'shadowed_collection_url' => [],
    'shadowed_metadata_url' => [],
    'shadowed_metadata_chapter_id' => [],
];
foreach ($sources as $file => $source) {
    $number = $source['number'];
    $rows = $hadithByNumber[$number] ?? [];
    $hadith = count($rows) === 1 ? $rows[0] : null;
    $sourceFilesInDb = $hadith === null ? [] : array_column($sourceByHadith[(string) $hadith['id']] ?? [], 'source_file');
    $fileMatches[$file] = [
        'hadith_number' => $number,
        'db_rows' => count($rows),
        'source_file_match' => in_array('nawawi40/'.$file, $sourceFilesInDb, true),
    ];
    $texts = $source['json']['hadith_text'] ?? [];
    $json = $source['json'];
    $storedUrl = $hadith === null ? null : ($sourceByHadith[(string) $hadith['id']][0]['url'] ?? null);
    foreach ([
        'hadith.matn.arabic.full_text' => [$json['hadith']['matn']['arabic']['full_text'] ?? null, $texts['ar'] ?? null],
        'hadith.matn.arabic.simplified' => [$json['hadith']['matn']['arabic']['simplified'] ?? null, $texts['ar_notashkeel'] ?? null],
        'hadith.matn.english.translation' => [$json['hadith']['matn']['english']['translation'] ?? null, $texts['en'] ?? null],
        'hadith_text_alternate.ar' => [$json['hadith_text_alternate']['ar'] ?? null, $texts['ar'] ?? null],
        'hadith_text_alternate.ar_notashkeel' => [$json['hadith_text_alternate']['ar_notashkeel'] ?? null, $texts['ar_notashkeel'] ?? null],
        'hadith_text_alternate.en' => [$json['hadith_text_alternate']['en'] ?? null, $texts['en'] ?? null],
    ] as $path => [$candidate, $landed]) {
        if ($candidate !== null && $candidate !== '' && $candidate !== $landed) {
            $partialLosses['alternate_hadith_content'][] = compact('file', 'number', 'path', 'candidate');
        }
    }
    foreach ([
        'shadowed_collection_url' => $json['collections'][0]['source_url'] ?? null,
        'shadowed_metadata_url' => $json['metadata']['sunnah_reference']['url'] ?? null,
    ] as $key => $candidate) {
        if ($candidate !== null && $candidate !== '' && $candidate !== $storedUrl) {
            $partialLosses[$key][] = compact('file', 'number', 'candidate', 'storedUrl');
        }
    }
    $sourceChapterId = isset($json['metadata']['chapter_id']) ? (string) $json['metadata']['chapter_id'] : null;
    if ($sourceChapterId !== null && $sourceChapterId !== ($chapterByNumber[$number] ?? null)) {
        $partialLosses['shadowed_metadata_chapter_id'][] = [
            'file' => $file,
            'number' => $number,
            'candidate' => $sourceChapterId,
            'stored_chapter_number' => $chapterByNumber[$number] ?? null,
        ];
    }
    $textChecks[$file] = [
        'hadith_number' => $number,
        'ar' => isset($translationByNumber[$number]['ar'])
            && hash('sha256', (string) ($texts['ar'] ?? '')) === $translationByNumber[$number]['ar']['text_sha256'],
        'en' => isset($translationByNumber[$number]['en'])
            && hash('sha256', (string) ($texts['en'] ?? '')) === $translationByNumber[$number]['en']['text_sha256'],
        'ar_notashkeel' => isset($translationByNumber[$number]['ar'])
            && (isset($texts['ar_notashkeel'])
                ? hash('sha256', (string) $texts['ar_notashkeel']) === $translationByNumber[$number]['ar']['search_sha256']
                : $translationByNumber[$number]['ar']['search_sha256'] === null),
        'hadith_text_hash' => $hadith !== null && hash('sha256', (string) ($texts['ar'] ?? '')) === $hadith['text_hash'],
    ];
}

$themesByHadith = [];
foreach ($pdo->query(
    'SELECT h.hadith_number,t.name FROM hadith_hadith_themes ht '
    .'JOIN hadith_hadiths h ON h.id=ht.hadith_id JOIN hadith_themes t ON t.id=ht.hadith_theme_id'
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $themesByHadith[(string) $row['hadith_number']][] = json_decode($row['name'], true, flags: JSON_THROW_ON_ERROR);
}

$keywordsByHadith = [];
foreach ($pdo->query(
    'SELECT h.hadith_number,k.locale,k.keyword FROM hadith_keywords k JOIN hadith_hadiths h ON h.id=k.hadith_id'
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $keywordsByHadith[(string) $row['hadith_number']][] = [(string) $row['locale'], (string) $row['keyword']];
}

$isnadByHadith = [];
foreach ($pdo->query(
    'SELECT h.hadith_number,i.position_in_chain,n.name FROM hadith_isnad i '
    .'JOIN hadith_hadiths h ON h.id=i.hadith_id JOIN hadith_narrators n ON n.id=i.narrator_id'
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $isnadByHadith[(string) $row['hadith_number']][] = [
        'position' => (int) $row['position_in_chain'],
        'name' => json_decode($row['name'], true, flags: JSON_THROW_ON_ERROR),
    ];
}

$gradingByHadith = [];
foreach ($pdo->query(
    'SELECT h.hadith_number,g.raw_grade,g.grade_key,s.name scholar_name FROM hadith_gradings g '
    .'JOIN hadith_hadiths h ON h.id=g.hadith_id LEFT JOIN hadith_scholars s ON s.id=g.scholar_id'
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $name = $row['scholar_name'] === null ? [] : json_decode($row['scholar_name'], true, flags: JSON_THROW_ON_ERROR);
    $gradingByHadith[(string) $row['hadith_number']][] = [
        'raw' => (string) $row['raw_grade'],
        'scholar' => $name === [] ? '' : scholarIdentity($name),
        'grade_key' => (string) $row['grade_key'],
    ];
}

$commentaryByHadith = [];
foreach ($pdo->query(
    'SELECT h.hadith_number,c.locale,c.text,s.name scholar_name FROM hadith_commentaries c '
    .'JOIN hadith_hadiths h ON h.id=c.hadith_id LEFT JOIN hadith_scholars s ON s.id=c.scholar_id'
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $name = $row['scholar_name'] === null ? [] : json_decode($row['scholar_name'], true, flags: JSON_THROW_ON_ERROR);
    $commentaryByHadith[(string) $row['hadith_number']][] = [
        'locale' => (string) $row['locale'],
        'text' => (string) $row['text'],
        'scholar' => $name === [] ? '' : scholarIdentity($name),
    ];
}

$relatedByHadith = [];
foreach ($pdo->query(
    'SELECT h.hadith_number,q.surah_id,q.verse_number,r.relevance FROM hadith_related_verses r '
    .'JOIN hadith_hadiths h ON h.id=r.hadith_id JOIN quran_verses q ON q.id=r.quran_verse_id'
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $relatedByHadith[(string) $row['hadith_number']][] = [
        'coordinate' => ((int) $row['surah_id']).':'.((int) $row['verse_number']),
        'relevance' => $row['relevance'] === null ? null : json_decode($row['relevance'], true, flags: JSON_THROW_ON_ERROR),
    ];
}

$entityChecks = [];
$sourceTotals = ['themes' => 0, 'keywords' => 0, 'isnad' => 0, 'gradings' => 0, 'commentaries' => 0, 'related_verses' => 0];
$themeUnique = $keywordCaseChanges = [];
$taxonomyRedundancy = [
    'tags' => ['total' => 0, 'exact_keyword' => 0, 'folded_keyword' => 0],
    'concepts' => ['total' => 0, 'exact_keyword' => 0, 'folded_keyword' => 0],
];
$sourcePeople = $sourceScholars = [];
foreach ($sources as $file => $source) {
    $json = $source['json'];
    $number = $source['number'];
    $themes = themeRows($json);
    $keywords = keywordRows($json);
    $normalizedKeywords = array_map(static fn (array $row): array => [$row[0], mb_strtolower(trim($row[1]))], $keywords);
    $gradings = gradingRows($json);
    $commentaries = commentaryRows($json);
    $related = relatedRows($json);
    $chain = $json['sanad']['narrators'] ?? $json['hadith']['sanad']['chain'] ?? [];
    $expectedIsnad = [];
    foreach ($chain as $entry) {
        $person = is_array($entry['narrator'] ?? null) ? $entry['narrator'] : $entry;
        if (! is_array($person) || personName($person) === []) {
            continue;
        }
        $position = (int) ($entry['order'] ?? $person['order'] ?? 0);
        $expectedIsnad[] = ['position' => $position, 'name' => personName($person)];
        $sourcePeople[personIdentity(personName($person))] = personName($person);
    }
    if (is_array($json['companion'] ?? null) && personName($json['companion']) !== []) {
        $sourcePeople[personIdentity(personName($json['companion']))] = personName($json['companion']);
    }
    foreach ($gradings as $row) {
        if ($row['scholar'] !== '') {
            $sourceScholars[$row['scholar']] ??= null;
        }
    }
    foreach ($commentaries as $row) {
        if ($row['scholar'] !== '') {
            $sourceScholars[$row['scholar']] ??= null;
        }
    }
    foreach ($themes as $row) {
        $themeUnique[signature($row)] = true;
    }
    foreach (array_map(null, $keywords, $normalizedKeywords) as [$original, $normalized]) {
        if ($original !== $normalized) {
            $keywordCaseChanges[] = ['file' => $file, 'hadith_number' => $number, 'source' => $original, 'db_expected' => $normalized];
        }
    }
    $dbKeywordValues = array_column($keywordsByHadith[$number] ?? [], 1);
    foreach (['tags' => $json['hadith']['tags'] ?? [], 'concepts' => $json['hadith']['keywords']['concepts'] ?? []] as $key => $values) {
        foreach ($values as $value) {
            $taxonomyRedundancy[$key]['total']++;
            if (in_array((string) $value, $dbKeywordValues, true)) {
                $taxonomyRedundancy[$key]['exact_keyword']++;
            }
            if (in_array(mb_strtolower(trim((string) $value)), $dbKeywordValues, true)) {
                $taxonomyRedundancy[$key]['folded_keyword']++;
            }
        }
    }
    $sourceTotals['themes'] += count($themes);
    $sourceTotals['keywords'] += count($keywords);
    $sourceTotals['isnad'] += count($expectedIsnad);
    $sourceTotals['gradings'] += count($gradings);
    $sourceTotals['commentaries'] += count($commentaries);
    $sourceTotals['related_verses'] += count($related);
    $entityChecks[$file] = [
        'hadith_number' => $number,
        'themes_exact' => sortedSignatures($themes) === sortedSignatures($themesByHadith[$number] ?? []),
        'keywords_correspond_after_import_normalization' => sortedSignatures($normalizedKeywords) === sortedSignatures($keywordsByHadith[$number] ?? []),
        'keywords_verbatim' => sortedSignatures($keywords) === sortedSignatures($keywordsByHadith[$number] ?? []),
        'isnad_names_positions_exact' => sortedSignatures($expectedIsnad) === sortedSignatures($isnadByHadith[$number] ?? []),
        'gradings_raw_scholar_key_exact' => sortedSignatures($gradings) === sortedSignatures($gradingByHadith[$number] ?? []),
        'commentaries_text_locale_scholar_exact' => sortedSignatures($commentaries) === sortedSignatures($commentaryByHadith[$number] ?? []),
        'related_coordinate_relevance_exact' => sortedSignatures($related) === sortedSignatures($relatedByHadith[$number] ?? []),
    ];
}

$duplicateSql = [
    'hadith_number' => 'SELECT hadith_number,COUNT(*) n FROM hadith_hadiths GROUP BY hadith_number HAVING n>1',
    'text_hash' => 'SELECT text_hash,COUNT(*) n FROM hadith_hadiths GROUP BY text_hash HAVING n>1',
    'translation_locale' => 'SELECT hadith_id,locale,COUNT(*) n FROM hadith_translations GROUP BY hadith_id,locale HAVING n>1',
    'theme_name_json' => 'SELECT name,COUNT(*) n FROM hadith_themes GROUP BY name HAVING n>1',
    'keyword_hadith_locale_folded' => 'SELECT hadith_id,locale,LOWER(TRIM(keyword)) keyword,COUNT(*) n FROM hadith_keywords GROUP BY hadith_id,locale,LOWER(TRIM(keyword)) HAVING n>1',
    'grading_hadith_scholar_grade' => 'SELECT hadith_id,COALESCE(scholar_id,0) scholar_id,grade_key,COUNT(*) n FROM hadith_gradings GROUP BY hadith_id,COALESCE(scholar_id,0),grade_key HAVING n>1',
    'isnad_position' => 'SELECT hadith_id,position_in_chain,COUNT(*) n FROM hadith_isnad GROUP BY hadith_id,position_in_chain HAVING n>1',
];
$duplicates = [];
foreach ($duplicateSql as $key => $sql) {
    $duplicates[$key] = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$duplicatePeople = [];
foreach (['narrators' => 'hadith_narrators', 'scholars' => 'hadith_scholars', 'themes' => 'hadith_themes'] as $label => $table) {
    foreach (['ar', 'en'] as $locale) {
        $sql = "SELECT LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(name,'$.{$locale}')))) normalized_name,"
            ."COUNT(*) n,GROUP_CONCAT(id ORDER BY id) ids FROM {$table} "
            ."WHERE JSON_EXTRACT(name,'$.{$locale}') IS NOT NULL GROUP BY normalized_name HAVING n>1";
        $duplicatePeople["{$label}_{$locale}"] = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}

$duplicateEntityRows = [];
foreach ($duplicatePeople as $key => $groups) {
    [$label] = explode('_', $key, 2);
    $table = ['narrators' => 'hadith_narrators', 'scholars' => 'hadith_scholars', 'themes' => 'hadith_themes'][$label];
    foreach ($groups as $group) {
        $ids = array_map('intval', explode(',', $group['ids']));
        foreach ($dbRows[$table] as $row) {
            if (in_array((int) $row['id'], $ids, true)) {
                $duplicateEntityRows[$key][$group['normalized_name']][] = [
                    'id' => (int) $row['id'],
                    'name' => json_decode($row['name'], true, flags: JSON_THROW_ON_ERROR),
                ];
            }
        }
    }
}

$entityUsage = [];
foreach ($pdo->query(
    "SELECT 'narrator' entity,n.id entity_id,h.hadith_number,s.source_file FROM hadith_narrators n "
    .'JOIN hadith_isnad i ON i.narrator_id=n.id JOIN hadith_hadiths h ON h.id=i.hadith_id '
    .'JOIN hadith_sources s ON s.hadith_id=h.id UNION ALL '
    ."SELECT 'narrator',n.id,h.hadith_number,s.source_file FROM hadith_narrators n "
    .'JOIN hadith_hadiths h ON h.primary_narrator_id=n.id JOIN hadith_sources s ON s.hadith_id=h.id UNION ALL '
    ."SELECT 'scholar',sc.id,h.hadith_number,src.source_file FROM hadith_scholars sc "
    .'JOIN hadith_gradings g ON g.scholar_id=sc.id JOIN hadith_hadiths h ON h.id=g.hadith_id '
    .'JOIN hadith_sources src ON src.hadith_id=h.id UNION ALL '
    ."SELECT 'scholar',sc.id,h.hadith_number,src.source_file FROM hadith_scholars sc "
    .'JOIN hadith_commentaries c ON c.scholar_id=sc.id JOIN hadith_hadiths h ON h.id=c.hadith_id '
    .'JOIN hadith_sources src ON src.hadith_id=h.id UNION ALL '
    ."SELECT 'theme',t.id,h.hadith_number,src.source_file FROM hadith_themes t "
    .'JOIN hadith_hadith_themes ht ON ht.hadith_theme_id=t.id JOIN hadith_hadiths h ON h.id=ht.hadith_id '
    .'JOIN hadith_sources src ON src.hadith_id=h.id'
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $entityUsage[$row['entity']][(string) $row['entity_id']][] = [
        'hadith_number' => (string) $row['hadith_number'],
        'source_file' => (string) $row['source_file'],
    ];
}

$quranTexts = [];
foreach ($pdo->query(
    'SELECT q.surah_id,q.verse_number,t.lang,t.text FROM quran_verse_translations t '
    .'JOIN quran_verses q ON q.id=t.verse_id'
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $quranTexts[((int) $row['surah_id']).':'.((int) $row['verse_number'])][(string) $row['lang']][(string) $row['text']] = true;
}
$quranSurahs = [];
foreach ($pdo->query('SELECT id,name_ar,name_en FROM quran_surahs')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $quranSurahs[(int) $row['id']] = ['ar' => (string) $row['name_ar'], 'en' => (string) $row['name_en']];
}
$quranRedundancyFailures = [];
foreach ($sources as $file => $source) {
    foreach ($source['json']['related_verses'] ?? [] as $verse) {
        foreach (ayahs($verse['ayah'] ?? null) as $ayah) {
            $surah = (int) ($verse['surah'] ?? 0);
            $coordinate = "{$surah}:{$ayah}";
            foreach (['ar' => 'text_ar', 'en' => 'text_en'] as $locale => $key) {
                $text = $verse[$key] ?? null;
                if (is_scalar($text) && trim((string) $text) !== '' && ! isset($quranTexts[$coordinate][$locale][(string) $text])) {
                    $quranRedundancyFailures[] = compact('file', 'coordinate', 'locale', 'text');
                }
            }
            foreach (['ar' => 'surah_name_ar', 'en' => 'surah_name_en'] as $locale => $key) {
                $name = $verse[$key] ?? null;
                if (is_scalar($name) && trim((string) $name) !== '' && (string) $name !== ($quranSurahs[$surah][$locale] ?? null)) {
                    $quranRedundancyFailures[] = ['file' => $file, 'coordinate' => $coordinate, 'locale' => "surah_{$locale}", 'text' => $name];
                }
            }
        }
    }
}

$isnadContinuity = $pdo->query(
    'SELECT hadith_id,MIN(position_in_chain) min_position,MAX(position_in_chain) max_position,'
    .'COUNT(*) n,COUNT(DISTINCT position_in_chain) distinct_n FROM hadith_isnad GROUP BY hadith_id '
    .'HAVING min_position<>1 OR max_position<>n OR distinct_n<>n'
)->fetchAll(PDO::FETCH_ASSOC);

$identifierChecks = [
    'hadith_collections.code' => 'SELECT id,code value FROM hadith_collections WHERE BINARY code<>BINARY LOWER(code)',
    'hadith_collections.slug' => 'SELECT id,slug value FROM hadith_collections WHERE BINARY slug<>BINARY LOWER(slug)',
    'hadith_themes.slug' => 'SELECT id,slug value FROM hadith_themes WHERE slug IS NOT NULL AND BINARY slug<>BINARY LOWER(slug)',
    'hadith_translations.locale' => 'SELECT id,locale value FROM hadith_translations WHERE BINARY locale<>BINARY LOWER(locale)',
    'hadith_commentaries.locale' => 'SELECT id,locale value FROM hadith_commentaries WHERE BINARY locale<>BINARY LOWER(locale)',
    'hadith_keywords.locale' => 'SELECT id,locale value FROM hadith_keywords WHERE BINARY locale<>BINARY LOWER(locale)',
    'hadith_gradings.grade_key' => 'SELECT id,grade_key value FROM hadith_gradings WHERE BINARY grade_key<>BINARY LOWER(grade_key)',
    'hadith_sources.source_type' => 'SELECT id,source_type value FROM hadith_sources WHERE BINARY source_type<>BINARY LOWER(source_type)',
];
foreach (['hadith_gradings', 'hadith_commentaries', 'hadith_themes', 'hadith_keywords', 'hadith_related_verses'] as $table) {
    $identifierChecks["{$table}.review_status"] = "SELECT id,review_status value FROM {$table} WHERE review_status IS NOT NULL AND BINARY review_status<>BINARY LOWER(review_status)";
}
$identifierViolations = [];
foreach ($identifierChecks as $key => $sql) {
    $identifierViolations[$key] = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$foreignKeys = $pdo->query(
    'SELECT table_name table_name,column_name column_name,referenced_table_name referenced_table_name,'
    .'referenced_column_name referenced_column_name,constraint_name constraint_name '
    ."FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name LIKE 'hadith\\_%' "
    .'AND referenced_table_name IS NOT NULL ORDER BY table_name,constraint_name,ordinal_position'
)->fetchAll(PDO::FETCH_ASSOC);
$orphans = [];
foreach ($foreignKeys as $fk) {
    $sql = "SELECT COUNT(*) FROM `{$fk['table_name']}` c LEFT JOIN `{$fk['referenced_table_name']}` p "
        ."ON p.`{$fk['referenced_column_name']}`=c.`{$fk['column_name']}` "
        ."WHERE c.`{$fk['column_name']}` IS NOT NULL AND p.`{$fk['referenced_column_name']}` IS NULL";
    $orphans[$fk['constraint_name']] = [
        'edge' => "{$fk['table_name']}.{$fk['column_name']}->{$fk['referenced_table_name']}.{$fk['referenced_column_name']}",
        'count' => (int) $pdo->query($sql)->fetchColumn(),
    ];
}

$requiredHierarchy = [
    'hadith_without_chapter' => (int) $pdo->query('SELECT COUNT(*) FROM hadith_hadiths h LEFT JOIN hadith_chapters c ON c.id=h.chapter_id WHERE c.id IS NULL')->fetchColumn(),
    'chapter_without_book' => (int) $pdo->query('SELECT COUNT(*) FROM hadith_chapters c LEFT JOIN hadith_books b ON b.id=c.book_id WHERE b.id IS NULL')->fetchColumn(),
    'book_without_collection' => (int) $pdo->query('SELECT COUNT(*) FROM hadith_books b LEFT JOIN hadith_collections c ON c.id=b.collection_id WHERE c.id IS NULL')->fetchColumn(),
];

$autoIncrement = [];
foreach ($pdo->query(
    'SELECT table_name table_name,auto_increment auto_increment FROM information_schema.tables WHERE table_schema=DATABASE() '
    ."AND table_name LIKE 'hadith\\_%' ORDER BY table_name"
)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $table = $row['table_name'];
    $hasId = in_array('id', array_column($pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC), 'Field'), true);
    $autoIncrement[$table] = [
        'auto_increment' => $row['auto_increment'] === null ? null : (int) $row['auto_increment'],
        'max_id' => $hasId ? (int) $pdo->query("SELECT COALESCE(MAX(id),0) FROM `{$table}`")->fetchColumn() : null,
    ];
}

$gradeDomain = $pdo->query(
    "SELECT id,raw_grade,grade_key FROM hadith_gradings WHERE grade_key NOT IN ('sahih','hasan','daif','mawdu','unknown')"
)->fetchAll(PDO::FETCH_ASSOC);

$pdo->exec('ROLLBACK');

$report = [
    'database' => [
        'host' => (string) config('database.connections.mysql.host'),
        'port' => (string) config('database.connections.mysql.port'),
        'schema' => DB::connection()->getDatabaseName(),
        'session' => 'START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY',
        'start_counts' => $startCounts,
    ],
    'source' => [
        'files' => count($sources),
        'flat_files' => count(array_filter($sources, static fn (array $source): bool => array_key_exists('themes_ar', $source['json']))),
        'nested_files' => count(array_filter($sources, static fn (array $source): bool => isset($source['json']['hadith']['themes']))),
        'path_inventory' => $inventory,
        'path_dispositions' => $pathDispositions,
        'disposition_totals' => $dispositionTotals,
        'entity_totals' => $sourceTotals,
        'unique_theme_rows' => count($themeUnique),
        'unique_people' => count($sourcePeople),
        'unique_scholars' => count($sourceScholars),
        'keyword_case_changes' => $keywordCaseChanges,
        'taxonomy_redundancy' => $taxonomyRedundancy,
    ],
    'portal_integrity' => $portalIntegrity,
    'portal_db_comparison' => $portalComparison,
    'file_matches' => $fileMatches,
    'text_checks' => $textChecks,
    'entity_checks' => $entityChecks,
    'db_counts' => array_map('count', $dbRows),
    'duplicates' => $duplicates + $duplicatePeople,
    'duplicate_entity_rows' => $duplicateEntityRows,
    'entity_usage' => $entityUsage,
    'quran_redundancy_failures' => $quranRedundancyFailures,
    'partial_losses' => $partialLosses + ['quran_text_or_name_not_exact_in_db' => $quranRedundancyFailures],
    'isnad_continuity_failures' => $isnadContinuity,
    'grade_domain_failures' => $gradeDomain,
    'identifier_violations' => $identifierViolations,
    'foreign_key_orphans' => $orphans,
    'required_hierarchy_failures' => $requiredHierarchy,
    'auto_increment' => $autoIncrement,
];

echo json_encode($report, $jsonFlags | JSON_PRETTY_PRINT).PHP_EOL;
