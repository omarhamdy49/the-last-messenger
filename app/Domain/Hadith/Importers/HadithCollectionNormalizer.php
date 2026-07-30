<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Importers;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Normalizer;

class HadithCollectionNormalizer
{
    public const TABLES = [
        'hadith_collections',
        'hadith_books',
        'hadith_chapters',
        'hadith_scholars',
        'hadith_themes',
        'hadith_narrators',
        'hadith_hadiths',
        'hadith_translations',
        'hadith_sources',
        'hadith_gradings',
        'hadith_isnad',
        'hadith_hadith_themes',
        'hadith_keywords',
        'hadith_commentaries',
        'hadith_related_verses',
        'hadith_applications',
        'hadith_fiqh_rulings',
        'hadith_sync_logs',
    ];

    private array $rows = [];

    private array $report = [];

    private array $quranVerseIds = [];

    private string $collectionCode;

    public function normalize(string $collectionCode, array $sources, array $quranVerseIds): array
    {
        $this->reset($collectionCode, count($sources), $quranVerseIds);

        foreach ($sources as $source) {
            $this->normalizeSource($source['path'], $source['json']);
        }

        $this->finishReport();

        return [
            'collection' => $collectionCode,
            'tables' => $this->orderedTables(),
            'report' => $this->report,
        ];
    }

    public function relatedVerseCoordinates(array $sources): array
    {
        $coordinates = [];

        foreach ($sources as $source) {
            foreach ($source['json']['related_verses'] ?? [] as $verse) {
                $surah = (int) ($verse['surah'] ?? 0);
                foreach ($this->ayahNumbers($verse['ayah'] ?? null) as $ayah) {
                    $coordinates["{$surah}:{$ayah}"] = ['surah' => $surah, 'ayah' => $ayah];
                }
            }
        }

        return array_values($coordinates);
    }

    private function reset(string $collectionCode, int $fileCount, array $quranVerseIds): void
    {
        $this->collectionCode = $collectionCode;
        $this->quranVerseIds = $quranVerseIds;
        $this->rows = array_fill_keys(self::TABLES, []);
        $this->report = [
            'files_read' => $fileCount,
            'inputs' => array_fill_keys([
                'narrators',
                'isnad',
                'gradings',
                'themes',
                'keywords',
                'commentaries',
                'related_verses',
                'applications',
                'fiqh_rulings',
            ], 0),
            'outputs' => [],
            'unmapped_grading_statuses' => [],
            'companions' => ['populated' => 0, 'linked' => 0],
            'authenticated_by_skipped_as_provenance' => 0,
            'keyword_sources' => [
                'keywords' => 0,
                'tags' => 0,
                'concepts' => 0,
            ],
            'captured_values' => ['applications' => 0, 'fiqh_rulings' => 0],
            'skipped_schema_fields' => array_fill_keys([
                'uncertainty_notes',
                'significance_ar',
                'significance_en',
                'historical_context',
                'grading_agreement',
            ], 0),
            'legacy_variants' => $this->emptyLegacyReport(),
        ];
    }

    private function normalizeSource(string $path, array $json): void
    {
        $this->trackSkippedSchemaFields($json);
        $this->trackLegacyVariants($json);

        $metadata = array_replace_recursive($json['_metadata'] ?? [], $json['metadata'] ?? []);
        $collection = $this->primaryCollection($json, $metadata);
        $externalId = $this->externalId($json, $metadata);
        $hadithId = $this->stableId('hadith', "{$this->collectionCode}|{$externalId}");
        $bookNumber = $this->bookNumber($json, $collection, $metadata);
        $bookId = $this->stableId('book', "{$this->collectionCode}|{$bookNumber}");
        $chapterNumber = $this->chapterNumber($json, $collection, $metadata);
        $chapterId = $this->stableId('chapter', "{$bookId}|{$chapterNumber}");

        $this->addCollection($json, $collection, $metadata);
        $this->addBook($json, $collection, $bookId, $bookNumber);
        $this->addChapter($json, $collection, $bookId, $chapterId, $chapterNumber);

        $primaryNarratorId = $this->addCompanion($json);
        $chainPrimaryId = $this->addNarrators($json, $hadithId);
        $primaryNarratorId ??= $chainPrimaryId;

        $hadithNumber = $this->hadithNumber($json, $collection, $metadata);
        $this->addHadith($json, $hadithId, $chapterId, $primaryNarratorId, $hadithNumber);
        $this->addTranslations($json, $hadithId);
        $this->addSource($json, $metadata, $hadithId, $externalId, $hadithNumber, $path);
        $this->addGradings($json, $hadithId, $hadithNumber);
        $this->addThemes($json, $hadithId);
        $this->addKeywords($json, $hadithId);
        $this->addCommentaries($json, $hadithId);
        $this->addRelatedVerses($json, $hadithId, $path);
        $this->addApplications($json, $hadithId);
        $this->addFiqhRulings($json, $hadithId);
    }

    private function addCollection(array $json, array $collection, array $metadata): void
    {
        $metadataCollection = $metadata['collection'] ?? [];
        $name = $this->localized(
            $metadataCollection['name_ar'] ?? $collection['name_ar'] ?? null,
            $metadataCollection['name_en']
                ?? $collection['name_en']
                ?? $json['collection_name']
                ?? $json['hadith']['collection']['name']
                ?? null,
        );
        $slug = Str::slug((string) ($name['en'] ?? $this->collectionCode));

        $this->put('hadith_collections', $this->collectionCode, [
            'id' => $this->collectionId(),
            'code' => $this->collectionCode,
            'slug' => strtolower($slug !== '' ? $slug : $this->collectionCode),
            'name' => $name,
            'description' => null,
            'abbreviation' => null,
            'featured' => false,
            'active' => true,
            'display_order' => 0,
        ]);
    }

    private function addBook(
        array $json,
        array $collection,
        int $bookId,
        string $bookNumber,
    ): void {
        $metadataCollection = $json['metadata']['collection'] ?? [];
        $title = $this->localized(
            $collection['book_name_ar']
                ?? $json['hadith']['book']['arabic_name']
                ?? $metadataCollection['name_ar']
                ?? null,
            $collection['book_name_en']
                ?? $json['hadith']['book']['name']
                ?? $metadataCollection['name_en']
                ?? null,
        );

        $this->put('hadith_books', (string) $bookId, [
            'id' => $bookId,
            'collection_id' => $this->collectionId(),
            'book_number' => $bookNumber,
            'title' => $title,
            'description' => null,
        ]);
    }

    private function addChapter(
        array $json,
        array $collection,
        int $bookId,
        int $chapterId,
        string $chapterNumber,
    ): void {
        $name = $chapterNumber === '0'
            ? ['ar' => 'بدون باب', 'en' => 'Unsectioned']
            : $this->localized(
                $collection['chapter_name_ar']
                    ?? $json['hadith']['chapter']['arabic_title']
                    ?? null,
                $collection['chapter_name_en']
                    ?? $json['hadith']['chapter']['title']
                    ?? null,
            );

        $this->put('hadith_chapters', (string) $chapterId, [
            'id' => $chapterId,
            'book_id' => $bookId,
            'chapter_number' => $chapterNumber,
            'name' => $name,
            'description' => null,
            'display_order' => (int) $chapterNumber,
        ]);
    }

    private function addHadith(
        array $json,
        int $hadithId,
        int $chapterId,
        ?int $primaryNarratorId,
        string $hadithNumber,
    ): void {
        $arabic = $this->requiredText($json['hadith_text']['ar'] ?? null, 'hadith_text.ar');

        $this->put('hadith_hadiths', (string) $hadithId, [
            'id' => $hadithId,
            'chapter_id' => $chapterId,
            'primary_narrator_id' => $primaryNarratorId,
            'hadith_number' => $hadithNumber,
            'text_hash' => hash('sha256', $arabic),
        ]);
    }

    private function addTranslations(array $json, int $hadithId): void
    {
        $texts = $json['hadith_text'];
        $note = $this->optionalText($json['note'] ?? null);
        $source = $this->sourceUrl($json);
        $reference = $json['metadata']['sunnah_reference'] ?? [];

        $this->put('hadith_translations', "{$hadithId}|ar", [
            'id' => $this->stableId('translation', "{$hadithId}|ar"),
            'hadith_id' => $hadithId,
            'locale' => 'ar',
            'text' => $this->requiredText($texts['ar'] ?? null, 'hadith_text.ar'),
            'search_text' => $this->optionalText($texts['ar_notashkeel'] ?? null),
            'translator_name' => null,
            'source_urn' => $this->numericOrNull($reference['urn'] ?? null),
            'source_url' => $source,
            'notes' => $note,
        ]);

        if ($this->optionalText($texts['en'] ?? null) !== null) {
            $this->put('hadith_translations', "{$hadithId}|en", [
                'id' => $this->stableId('translation', "{$hadithId}|en"),
                'hadith_id' => $hadithId,
                'locale' => 'en',
                'text' => $texts['en'],
                'search_text' => null,
                'translator_name' => null,
                'source_urn' => $this->numericOrNull($reference['urn_en'] ?? null),
                'source_url' => $source,
                'notes' => $note,
            ]);
        }
    }

    private function addSource(
        array $json,
        array $metadata,
        int $hadithId,
        string $externalId,
        string $hadithNumber,
        string $path,
    ): void {
        $collection = $metadata['collection'] ?? [];
        $verifiedAt = $this->optionalText($json['processed_at'] ?? null);

        $this->put('hadith_sources', $externalId, [
            'id' => $this->stableId('source', "prophet_data_generator|{$externalId}"),
            'hadith_id' => $hadithId,
            'source_system' => 'prophet_data_generator',
            'source_type' => 'json',
            'external_id' => $externalId,
            'source_file' => $this->collectionCode.'/'.basename($path),
            'title' => $this->optionalText($collection['name_en'] ?? null),
            'author' => $this->optionalText($collection['author_en'] ?? null),
            'reference' => $hadithNumber,
            'url' => $this->sourceUrl($json),
            'verification_method' => $this->identifier(
                $json['source_verification']['sunnah']['status']
                    ?? $json['verification_status']
                    ?? null,
            ),
            'verified_at' => $verifiedAt,
        ]);
    }

    private function addCompanion(array $json): ?int
    {
        $companion = $json['companion'] ?? null;
        if (! is_array($companion) || $this->personName($companion) === []) {
            return null;
        }

        $this->report['companions']['populated']++;
        $id = $this->addNarratorIdentity($this->narratorRow($companion));
        $this->report['companions']['linked']++;

        return $id;
    }

    private function addNarrators(array $json, int $hadithId): ?int
    {
        $chain = $json['sanad']['narrators'] ?? $this->nestedChain($json);
        $primaryNarratorId = null;
        $positionShift = 0;

        foreach (array_values($chain) as $index => $entry) {
            $person = is_array($entry['narrator'] ?? null) ? $entry['narrator'] : $entry;
            if (! is_array($person) || $this->personName($person) === []) {
                continue;
            }

            $position = (int) ($entry['order'] ?? $person['order'] ?? 0);
            if ($position < 1) {
                throw new InvalidArgumentException('A populated narrator is missing a positive source order');
            }

            $splitPeople = $this->splitPersonRows($person);
            foreach ($splitPeople as $offset => $splitPerson) {
                $splitPosition = $position + $positionShift + $offset;
                $this->report['inputs']['narrators']++;
                $this->report['inputs']['isnad']++;
                $narratorId = $this->addNarratorIdentity($this->narratorRow($splitPerson));
                $primaryNarratorId = $splitPosition === 1 ? $narratorId : $primaryNarratorId;

                $this->put('hadith_isnad', "{$hadithId}|{$splitPosition}", [
                    'id' => $this->stableId('isnad', "{$hadithId}|{$splitPosition}"),
                    'hadith_id' => $hadithId,
                    'narrator_id' => $narratorId,
                    'position_in_chain' => $splitPosition,
                    'transmission_method' => $this->identifier(
                        $entry['transmission_verb']
                            ?? $person['transmission_method']
                            ?? null,
                    ),
                ]);
            }
            $positionShift += count($splitPeople) - 1;
        }

        return $primaryNarratorId;
    }

    private function addNarratorIdentity(array $row): int
    {
        $identity = $this->personIdentity($row['name']);
        $id = $this->stableId('narrator', $identity);
        $row['id'] = $id;
        $row['sahaba_id'] = null;

        if (isset($this->rows['hadith_narrators'][$identity])) {
            $row = $this->mergePersonRows($this->rows['hadith_narrators'][$identity], $row);
        }

        $this->put('hadith_narrators', $identity, $row);

        return $id;
    }

    private function addGradings(array $json, int $hadithId, string $reference): void
    {
        $grading = $json['grading'] ?? null;
        if (is_array($grading) && $this->optionalText($grading['status'] ?? null) !== null) {
            $this->addGradingRow($hadithId, 'source', $grading['status'], null, [
                'ar' => $grading['notes_ar'] ?? $grading['notes'] ?? null,
                'en' => $grading['notes_en'] ?? null,
            ], $reference, (bool) ($grading['verified'] ?? false));
        }

        if (is_array($grading) && $this->optionalText($grading['albani_grade'] ?? null) !== null) {
            $scholarId = $this->addScholar(['ar' => 'الشيخ الألباني', 'en' => 'Shaykh al-Albani']);
            $this->addGradingRow(
                $hadithId,
                'albani',
                $grading['albani_grade'],
                $scholarId,
                [],
                $reference,
                (bool) ($grading['verified'] ?? false),
            );
        }

        $this->report['authenticated_by_skipped_as_provenance'] += count(
            is_array($grading['authenticated_by'] ?? null) ? $grading['authenticated_by'] : [],
        );

        $nested = $json['hadith']['grading'] ?? [];
        if (is_array($nested['primary'] ?? null)) {
            $this->addNestedGrading($hadithId, 'nested_primary', $nested['primary'], $reference);
        }
        foreach ($nested['secondary'] ?? [] as $index => $secondary) {
            if (is_array($secondary)) {
                $this->addNestedGrading($hadithId, "nested_secondary_{$index}", $secondary, $reference);
            }
        }
    }

    private function addNestedGrading(
        int $hadithId,
        string $key,
        array $grading,
        string $reference,
    ): void {
        $rawGrade = $this->optionalText($grading['grade'] ?? null);
        if ($rawGrade === null) {
            return;
        }

        $scholars = $this->optionalText($grading['scholar'] ?? null) === null
            ? [null]
            : $this->addScholars(['en' => $grading['scholar']]);

        foreach ($scholars as $index => $scholarId) {
            $gradingKey = count($scholars) === 1 ? $key : "{$key}_{$index}";
            $this->addGradingRow(
                $hadithId,
                $gradingKey,
                $rawGrade,
                $scholarId,
                ['ar' => $grading['arabic'] ?? null, 'en' => $grading['explanation'] ?? null],
                (string) ($grading['reference'] ?? $reference),
                true,
                $grading['source'] ?? null,
            );
        }
    }

    private function addGradingRow(
        int $hadithId,
        string $key,
        string $rawGrade,
        ?int $scholarId,
        array $notes,
        string $reference,
        bool $verified,
        ?string $sourceName = null,
    ): void {
        if (mb_strlen($rawGrade) > 512) {
            throw new InvalidArgumentException("raw_grade exceeds 512 characters: {$rawGrade}");
        }

        $this->report['inputs']['gradings']++;
        $this->put('hadith_gradings', "{$hadithId}|{$key}", [
            'id' => $this->stableId('grading', "{$hadithId}|{$key}"),
            'hadith_id' => $hadithId,
            'scholar_id' => $scholarId,
            'grade_key' => $this->canonicalGradeKey($rawGrade),
            'raw_grade' => $rawGrade,
            'notes' => $this->localizedJson($notes),
            'source_reference' => $reference,
            'source_url' => null,
            'source_name' => $this->optionalText($sourceName),
            'review_status' => $verified ? 'source_verified' : 'generated_unreviewed',
            'evaluated_at' => null,
        ]);
    }

    private function addThemes(array $json, int $hadithId): void
    {
        $themesAr = $json['themes_ar'] ?? [];
        $themesEn = $json['themes_en'] ?? [];
        $count = max(count($themesAr), count($themesEn));

        for ($index = 0; $index < $count; $index++) {
            $this->addTheme($hadithId, $this->localized(
                $themesAr[$index] ?? null,
                $themesEn[$index] ?? null,
            ));
        }

        $this->addNestedThemes($json['hadith']['themes'] ?? [], $hadithId);
    }

    private function addTheme(int $hadithId, array $name): void
    {
        if ($name === []) {
            return;
        }

        $slug = $this->themeSlug($name);
        $themeId = $this->stableId('theme', $slug);
        $pivotKey = "{$hadithId}|{$themeId}";

        if (! isset($this->rows['hadith_hadith_themes'][$pivotKey])) {
            $this->report['inputs']['themes']++;
        }
        $this->put('hadith_themes', $slug, [
            'id' => $themeId,
            'slug' => $slug,
            'name' => $name,
            'review_status' => 'source_verified',
            'source_reference' => null,
        ]);
        $this->put('hadith_hadith_themes', $pivotKey, [
            'hadith_id' => $hadithId,
            'hadith_theme_id' => $themeId,
        ]);
    }

    private function addNestedThemes(array $themes, int $hadithId): void
    {
        $byArabic = [];
        $englishOnly = [];

        foreach ($themes as $theme) {
            if (! is_array($theme)) {
                continue;
            }
            $name = $this->localized(
                $theme['arabic'] ?? $theme['name_ar'] ?? null,
                $theme['theme'] ?? $theme['name_en'] ?? null,
            );
            if (! isset($name['ar'])) {
                $englishOnly[] = $name;

                continue;
            }
            $byArabic[$name['ar']][$name['en'] ?? ''] = $name['en'] ?? null;
        }

        foreach ($englishOnly as $name) {
            $this->addTheme($hadithId, $name);
        }
        foreach ($byArabic as $arabic => $englishByName) {
            $english = array_values(array_filter($englishByName));
            if (count($english) > 1) {
                $this->addTheme($hadithId, ['ar' => $arabic]);
                foreach ($english as $name) {
                    $this->addTheme($hadithId, ['en' => $name]);
                }

                continue;
            }
            $this->addTheme($hadithId, $this->localized($arabic, $english[0] ?? null));
        }
    }

    private function themeSlug(array $name): string
    {
        $base = (string) ($name['en'] ?? $name['ar']);
        $slug = Str::slug($base);
        $slug = strtolower($slug !== '' ? $slug : 'theme-'.hash('sha256', $base));
        $existing = $this->rows['hadith_themes'][$slug]['name'] ?? null;

        return $existing === null || $existing === $name
            ? $slug
            : $slug.'-'.substr(hash('sha256', json_encode($name, JSON_UNESCAPED_UNICODE)), 0, 16);
    }

    private function addKeywords(array $json, int $hadithId): void
    {
        foreach ($json['keywords'] ?? [] as $keyword) {
            if (is_scalar($keyword)) {
                $token = (string) $keyword;
                $this->addKeyword($hadithId, $this->isArabic($token) ? 'ar' : 'en', $token, 'keywords');
            }
        }

        foreach ($json['hadith']['keywords']['arabic'] ?? [] as $keyword) {
            $this->addKeyword($hadithId, 'ar', (string) $keyword, 'keywords');
        }
        foreach ($json['hadith']['keywords']['english'] ?? [] as $keyword) {
            $this->addKeyword($hadithId, 'en', (string) $keyword, 'keywords');
        }
        foreach ($json['hadith']['tags'] ?? [] as $keyword) {
            $token = (string) $keyword;
            $this->addKeyword($hadithId, $this->isArabic($token) ? 'ar' : 'en', $token, 'tags');
        }
        foreach ($json['hadith']['keywords']['concepts'] ?? [] as $keyword) {
            $token = (string) $keyword;
            $this->addKeyword($hadithId, $this->isArabic($token) ? 'ar' : 'en', $token, 'concepts');
        }
    }

    private function addKeyword(int $hadithId, string $locale, string $keyword, string $source): void
    {
        $token = mb_strtolower(trim($keyword));
        if ($token === '') {
            return;
        }
        if (mb_strlen($token) > 100) {
            throw new InvalidArgumentException("Keyword exceeds 100 characters: {$keyword}");
        }

        $key = "{$hadithId}|{$locale}|{$token}";
        if (isset($this->rows['hadith_keywords'][$key])) {
            return;
        }

        $this->report['inputs']['keywords']++;
        $this->report['keyword_sources'][$source]++;
        $this->put('hadith_keywords', $key, [
            'id' => $this->stableId('keyword', $key),
            'hadith_id' => $hadithId,
            'locale' => strtolower($locale),
            'keyword' => $token,
            'review_status' => 'source_verified',
            'source_reference' => null,
        ]);
    }

    private function addCommentaries(array $json, int $hadithId): void
    {
        foreach ($json['scholarly_comments'] ?? [] as $index => $comment) {
            if (! is_array($comment)) {
                continue;
            }
            $names = $this->localized(
                $comment['scholar_ar'] ?? null,
                $comment['scholar_en'] ?? $comment['scholar'] ?? null,
            );
            $this->addCommentary($hadithId, "top_{$index}_ar", 'ar', $comment['comment_ar'] ?? null, $names, $comment);
            $this->addCommentary($hadithId, "top_{$index}_en", 'en', $comment['comment_en'] ?? null, $names, $comment);
        }

        foreach ($json['hadith']['scholarly_commentary'] ?? [] as $index => $comment) {
            if (! is_array($comment)) {
                continue;
            }
            $names = $this->localized($comment['arabic_name'] ?? null, $comment['scholar'] ?? null);
            $this->addCommentary(
                $hadithId,
                "nested_{$index}_en",
                'en',
                $comment['commentary'] ?? null,
                $names,
                $comment,
            );
        }
    }

    private function addCommentary(
        int $hadithId,
        string $key,
        string $locale,
        mixed $text,
        array $names,
        array $source,
    ): void {
        $commentary = $this->optionalText($text);
        if ($commentary === null) {
            return;
        }

        $people = $names === [] ? [[]] : $this->splitPersonNames($names);
        foreach ($people as $index => $person) {
            $rowKey = count($people) === 1 ? $key : "{$key}_{$index}";
            $this->report['inputs']['commentaries']++;
            $this->put('hadith_commentaries', "{$hadithId}|{$rowKey}", [
                'id' => $this->stableId('commentary', "{$hadithId}|{$rowKey}"),
                'hadith_id' => $hadithId,
                'scholar_id' => $person === [] ? null : $this->addScholar($person),
                'locale' => strtolower($locale),
                'author_name' => $this->optionalText(
                    $person[$locale] ?? $person['en'] ?? $person['ar'] ?? null,
                ),
                'source_reference' => $this->optionalText($source['source'] ?? null),
                'source_url' => $this->optionalText($source['source_url'] ?? null),
                'review_status' => ($source['verified'] ?? false) ? 'source_verified' : 'generated_unreviewed',
                'text' => $commentary,
            ]);
        }
    }

    private function addRelatedVerses(array $json, int $hadithId, string $path): void
    {
        foreach ($json['related_verses'] ?? [] as $verse) {
            $surah = (int) ($verse['surah'] ?? 0);
            $ayahs = $this->ayahNumbers($verse['ayah'] ?? null);
            if ($surah < 1 || $ayahs === []) {
                throw new InvalidArgumentException('Invalid populated related_verses coordinate in '.basename($path));
            }

            foreach ($ayahs as $ayah) {
                $coordinate = "{$surah}:{$ayah}";
                $quranVerseId = $this->quranVerseIds[$coordinate] ?? null;
                if ($quranVerseId === null) {
                    throw new InvalidArgumentException("Quran verse {$coordinate} from ".basename($path).' does not exist');
                }

                $this->report['inputs']['related_verses']++;
                $key = "{$hadithId}|{$quranVerseId}";
                $this->put('hadith_related_verses', $key, [
                    'id' => $this->stableId('related_verse', $key),
                    'hadith_id' => $hadithId,
                    'quran_verse_id' => $quranVerseId,
                    'relevance' => $this->localized(
                        $verse['relevance_ar'] ?? null,
                        $verse['relevance_en'] ?? $verse['relevance'] ?? null,
                    ) ?: null,
                    'review_status' => 'source_verified',
                    'source_reference' => null,
                    'source_url' => null,
                ]);
            }
        }
    }

    private function addApplications(array $json, int $hadithId): void
    {
        foreach ($json['applications'] ?? [] as $index => $application) {
            if (is_array($application)) {
                $this->addApplication($hadithId, "flat_{$index}", [
                    'category' => $this->localized($application['category_ar'] ?? null, $application['category_en'] ?? null),
                    'description' => $this->localized($application['description_ar'] ?? null, $application['description_en'] ?? null),
                    'example' => null,
                    'display_order' => $index,
                    'source' => $application,
                ]);
            }
        }

        foreach ($json['hadith']['contemporary_applications'] ?? [] as $index => $application) {
            if (is_array($application)) {
                $this->addApplication($hadithId, "nested_{$index}", [
                    'category' => $this->localized(null, $application['area'] ?? null),
                    'description' => $this->localized(null, $application['application'] ?? null),
                    'example' => $this->localized(null, $application['example'] ?? null) ?: null,
                    'display_order' => $index,
                    'source' => $application,
                ]);
            }
        }
    }

    private function addApplication(int $hadithId, string $key, array $fields): void
    {
        if ($fields['description'] === []) {
            return;
        }

        $this->report['inputs']['applications']++;
        $this->report['captured_values']['applications'] += $this->scalarValueCount($fields['source']);
        $this->put('hadith_applications', "{$hadithId}|{$key}", [
            'id' => $this->stableId('application', "{$hadithId}|{$key}"),
            'hadith_id' => $hadithId,
            'category' => $fields['category'] ?: null,
            'description' => $fields['description'],
            'example' => $fields['example'],
            'display_order' => $fields['display_order'],
            'review_status' => 'source_verified',
        ]);
    }

    private function addFiqhRulings(array $json, int $hadithId): void
    {
        $legalRulings = $json['legal_rulings'] ?? [];
        $this->addLocalizedFiqhList(
            $hadithId,
            'fiqh_principle',
            $legalRulings['fiqh_principles_ar'] ?? [],
            $legalRulings['fiqh_principles_en'] ?? [],
        );

        $nested = $json['hadith']['fiqh_rulings'] ?? [];
        if (is_array($nested) && ! array_is_list($nested)) {
            $this->addNestedFiqhRulings($hadithId, $nested);
        }

        $bidah = $json['types_of_bidah'] ?? [];
        $this->addLocalizedFiqhList(
            $hadithId,
            'bidah_classification',
            $bidah['classifications_ar'] ?? [],
            $bidah['classifications_en'] ?? [],
        );
    }

    private function addLocalizedFiqhList(int $hadithId, string $type, array $arabic, array $english): void
    {
        for ($index = 0; $index < max(count($arabic), count($english)); $index++) {
            $ruling = $this->localized($arabic[$index] ?? null, $english[$index] ?? null);
            $this->addFiqhRuling($hadithId, "{$type}_{$index}", [
                'type' => $type,
                'title' => null,
                'ruling' => $ruling,
                'details' => null,
                'display_order' => $index,
            ]);
        }
    }

    private function addNestedFiqhRulings(int $hadithId, array $fiqh): void
    {
        $this->addFiqhRuling($hadithId, 'primary_ruling', [
            'type' => 'primary_ruling',
            'title' => null,
            'ruling' => $this->localized(null, $fiqh['primary_ruling'] ?? null),
            'details' => null,
            'display_order' => 0,
        ]);
        $this->addScalarFiqhRows($hadithId, 'general_principle', $fiqh['general_principles'] ?? []);
        $this->addScalarFiqhRows($hadithId, 'practical_application', $fiqh['practical_applications'] ?? []);
        $this->addStructuredFiqhRows(
            $hadithId,
            'detail',
            $fiqh['details'] ?? [],
            ['title' => 'aspect', 'ruling' => 'ruling'],
        );
        $this->addStructuredFiqhRows(
            $hadithId,
            'school_of_thought',
            $fiqh['schools_of_thought'] ?? [],
            ['title' => 'school', 'ruling' => 'position'],
        );
    }

    private function addScalarFiqhRows(int $hadithId, string $type, array $rulings): void
    {
        foreach ($rulings as $index => $ruling) {
            $this->addFiqhRuling($hadithId, "{$type}_{$index}", [
                'type' => $type,
                'title' => null,
                'ruling' => $this->localized(null, $ruling),
                'details' => null,
                'display_order' => $index,
            ]);
        }
    }

    private function addStructuredFiqhRows(
        int $hadithId,
        string $type,
        array $rulings,
        array $fields,
    ): void {
        foreach ($rulings as $index => $source) {
            if (! is_array($source)) {
                continue;
            }
            $details = array_diff_key($source, array_flip($fields));
            $this->addFiqhRuling($hadithId, "{$type}_{$index}", [
                'type' => $type,
                'title' => $this->localized(null, $source[$fields['title']] ?? null),
                'ruling' => $this->localized(null, $source[$fields['ruling']] ?? null),
                'details' => $details ?: null,
                'display_order' => $index,
            ]);
        }
    }

    private function addFiqhRuling(int $hadithId, string $key, array $fields): void
    {
        if (($fields['title'] ?? []) === []
            && $fields['ruling'] === []
            && ($fields['details'] ?? []) === []) {
            return;
        }

        $this->report['inputs']['fiqh_rulings']++;
        $this->report['captured_values']['fiqh_rulings'] += $this->scalarValueCount([
            'title' => $fields['title'],
            'ruling' => $fields['ruling'],
            'details' => $fields['details'],
        ]);
        $this->put('hadith_fiqh_rulings', "{$hadithId}|{$key}", [
            'id' => $this->stableId('fiqh_ruling', "{$hadithId}|{$key}"),
            'hadith_id' => $hadithId,
            'scholar_id' => null,
            'type' => $fields['type'],
            'title' => $fields['title'] ?: null,
            'ruling' => $fields['ruling'] ?: null,
            'details' => $fields['details'],
            'display_order' => $fields['display_order'],
            'review_status' => 'source_verified',
        ]);
    }

    private function addScholar(array $name): int
    {
        $identity = $this->scholarIdentity($name);
        $id = $this->stableId('scholar', $identity);
        $row = [
            'id' => $id,
            'name' => $name,
            'full_name' => null,
            'kunya' => null,
            'biography' => null,
            'school' => null,
            'birth_year_hijri' => null,
            'death_year_hijri' => null,
            'region' => null,
        ];

        if (isset($this->rows['hadith_scholars'][$identity])) {
            $row['name'] = $this->preferredPersonName(
                $this->rows['hadith_scholars'][$identity]['name'],
                $name,
            );
        }
        $this->put('hadith_scholars', $identity, $row);

        return $id;
    }

    private function addScholars(array $name): array
    {
        return array_map(fn (array $person): int => $this->addScholar($person), $this->splitPersonNames($name));
    }

    private function finishReport(): void
    {
        if ($this->report['files_read'] !== count($this->rows['hadith_hadiths'])) {
            throw new InvalidArgumentException(
                "Source file assertion failed: {$this->report['files_read']} files, "
                .count($this->rows['hadith_hadiths']).' normalized hadiths',
            );
        }

        $this->put('hadith_sync_logs', $this->collectionCode, [
            'id' => $this->stableId('sync_log', $this->collectionCode),
            'collection_id' => $this->collectionId(),
            'source_collection_code' => $this->collectionCode,
            'type' => 'json_import',
            'status' => 'completed',
            'metadata' => ['source' => 'prophet-data-generator/hadith/production'],
            'summary' => ['inputs' => $this->report['inputs']],
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ]);

        $this->report['outputs'] = [
            'narrators' => count($this->rows['hadith_isnad']),
            'isnad' => count($this->rows['hadith_isnad']),
            'gradings' => count($this->rows['hadith_gradings']),
            'themes' => count($this->rows['hadith_hadith_themes']),
            'keywords' => count($this->rows['hadith_keywords']),
            'commentaries' => count($this->rows['hadith_commentaries']),
            'related_verses' => count($this->rows['hadith_related_verses']),
            'applications' => count($this->rows['hadith_applications']),
            'fiqh_rulings' => count($this->rows['hadith_fiqh_rulings']),
        ];

        foreach ($this->report['inputs'] as $key => $count) {
            if ($count !== $this->report['outputs'][$key]) {
                throw new InvalidArgumentException(
                    "Field mapping assertion failed for {$key}: populated input {$count}, output {$this->report['outputs'][$key]}",
                );
            }
        }
    }

    private function canonicalGradeKey(string $rawGrade): string
    {
        $grade = mb_strtolower(trim($rawGrade));

        return match (true) {
            $grade === 'unknown',
            $grade === 'ungraded',
            $grade === 'not graded',
            $grade === 'n/a',
            str_contains($grade, 'غير معروف') => 'unknown',
            str_starts_with($grade, 'hasan'),
            str_starts_with($grade, 'حسن') => 'hasan',
            str_starts_with($grade, 'sahih'),
            str_starts_with($grade, 'صحيح') => 'sahih',
            str_contains($grade, 'mawdu'),
            str_contains($grade, 'fabricat'),
            str_contains($grade, 'موضوع') => 'mawdu',
            str_contains($grade, 'daif'),
            str_contains($grade, "da'if"),
            str_contains($grade, 'weak'),
            str_contains($grade, 'ضعيف') => 'daif',
            str_contains($grade, 'hasan'),
            str_contains($grade, 'حسن') => 'hasan',
            str_contains($grade, 'sahih'),
            str_contains($grade, 'authentic'),
            str_contains($grade, 'صحيح') => 'sahih',
            default => throw new InvalidArgumentException("Unmapped grading.status: {$rawGrade}"),
        };
    }

    private function narratorRow(array $person): array
    {
        $name = $this->personName($person);

        return [
            'name' => $name,
            'full_name' => $this->localized(
                $person['full_name_ar'] ?? $person['arabic_full_name'] ?? null,
                $person['full_name_en'] ?? $person['full_name'] ?? null,
            ) ?: null,
            'kunya' => $this->localized(
                $person['arabic_kunya'] ?? null,
                $person['kunya'] ?? null,
            ) ?: null,
            'biography' => $this->localized(
                $person['bio_ar'] ?? null,
                $person['bio_en'] ?? $person['biographical_notes'] ?? null,
            ) ?: null,
            'birth_year_hijri' => $this->hijriYear(
                $person['birth_year_hijri'] ?? $person['birth_year'] ?? null,
            ),
            'death_year_hijri' => $this->hijriYear(
                $person['death_year_hijri'] ?? $person['death_year'] ?? null,
            ),
            'reliability_grade' => $this->identifier(
                $person['reliability_en'] ?? $person['reliability'] ?? null,
                100,
            ),
            'school' => $this->optionalText($person['school'] ?? null),
            'region' => $this->optionalText($person['region'] ?? null),
        ];
    }

    private function personName(array $person): array
    {
        return $this->localized(
            $person['name_ar']
                ?? $person['narrator_ar']
                ?? $person['arabic_name']
                ?? null,
            $person['narrator_en']
                ?? $person['name_en']
                ?? $person['name']
                ?? null,
        );
    }

    private function mergePersonRows(array $current, array $incoming): array
    {
        $current['name'] = $this->preferredPersonName($current['name'], $incoming['name']);
        foreach (['full_name', 'kunya', 'biography'] as $field) {
            $current[$field] = array_replace($current[$field] ?? [], $incoming[$field] ?? []) ?: null;
        }
        foreach (['birth_year_hijri', 'death_year_hijri', 'reliability_grade', 'school', 'region'] as $field) {
            $current[$field] ??= $incoming[$field] ?? null;
        }

        return $current;
    }

    private function preferredPersonName(array $current, array $incoming): array
    {
        $score = static fn (array $name): array => [
            count($name),
            array_sum(array_map('mb_strlen', $name)),
        ];

        return $score($incoming) > $score($current)
            ? array_replace($current, $incoming)
            : array_replace($incoming, $current);
    }

    private function splitPersonRows(array $person): array
    {
        $names = $this->splitPersonNames($this->personName($person));

        return array_map(static function (array $name) use ($person): array {
            $row = array_diff_key($person, array_flip([
                'name_ar',
                'narrator_ar',
                'arabic_name',
                'narrator_en',
                'name_en',
                'name',
            ]));

            return $row + ['name_ar' => $name['ar'] ?? null, 'name_en' => $name['en'] ?? null];
        }, $names);
    }

    private function splitPersonNames(array $name): array
    {
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
    }

    private function nestedChain(array $json): array
    {
        $sanad = $json['hadith']['sanad'] ?? [];

        return match (true) {
            isset($sanad['chain']) && is_array($sanad['chain']) => $sanad['chain'],
            array_is_list($sanad) => $sanad,
            default => [],
        };
    }

    private function primaryCollection(array $json, array $metadata): array
    {
        $metadataCollection = $metadata['collection'] ?? [];

        foreach ($json['collections'] ?? [] as $collection) {
            if (
                (($metadataCollection['name_en'] ?? null) !== null
                    && ($collection['name_en'] ?? null) === $metadataCollection['name_en'])
                || (($metadataCollection['name_ar'] ?? null) !== null
                    && ($collection['name_ar'] ?? null) === $metadataCollection['name_ar'])
            ) {
                return $collection;
            }
        }

        return $json['collections'][0] ?? [];
    }

    private function externalId(array $json, array $metadata): string
    {
        $id = $metadata['id'] ?? $json['local_id'] ?? null;
        if (! is_scalar($id) || trim((string) $id) === '') {
            throw new InvalidArgumentException('Missing metadata.id/local_id');
        }

        return (string) $id;
    }

    private function bookNumber(array $json, array $collection, array $metadata): string
    {
        $number = $collection['book_number']
            ?? $metadata['sunnah_reference']['book_number']
            ?? $json['hadith']['book']['number']
            ?? '1';

        return trim((string) $number) !== '' ? (string) $number : '1';
    }

    private function chapterNumber(array $json, array $collection, array $metadata): string
    {
        $number = $collection['chapter_number']
            ?? $json['hadith']['chapter']['number']
            ?? $metadata['chapter_id']
            ?? 0;

        return (string) $number;
    }

    private function hadithNumber(array $json, array $collection, array $metadata): string
    {
        $number = $collection['hadith_number']
            ?? $metadata['sunnah_reference']['hadith_number']
            ?? $metadata['id_in_book']
            ?? null;
        if (! is_scalar($number) || (string) $number === '') {
            throw new InvalidArgumentException('Missing collection hadith_number');
        }

        return (string) $number;
    }

    private function sourceUrl(array $json): ?string
    {
        return $this->optionalText(
            $json['source_verification']['sunnah']['url']
                ?? $json['collections'][0]['source_url']
                ?? $json['metadata']['sunnah_reference']['url']
                ?? null,
        );
    }

    private function trackSkippedSchemaFields(array $json): void
    {
        $this->report['skipped_schema_fields']['uncertainty_notes'] += $this->payloadCount(
            $json['uncertainty_notes'] ?? null,
        );
        $this->report['skipped_schema_fields']['significance_ar'] += $this->payloadCount(
            $json['significance_ar'] ?? null,
        );
        $this->report['skipped_schema_fields']['significance_en'] += $this->payloadCount(
            $json['significance_en'] ?? null,
        );
        $this->report['skipped_schema_fields']['historical_context'] += $this->payloadCount(
            $json['historical_context'] ?? $json['hadith']['historical_context'] ?? null,
        );
        $this->report['skipped_schema_fields']['grading_agreement'] += $this->payloadCount(
            $json['grading']['agreement'] ?? null,
        );
    }

    private function trackLegacyVariants(array $json): void
    {
        $this->trackLegacy('_metadata.*', $json['_metadata'] ?? null, true);
        $this->trackLegacy('collection_name', $json['collection_name'] ?? null, true);
        $this->trackLegacy('local_id', $json['local_id'] ?? null, true);
        $this->trackLegacy('context_ar', $json['context_ar'] ?? null, false);
        $this->trackLegacy('context_en', $json['context_en'] ?? null, false);
        $this->trackLegacy('legal_maxims[]', $json['legal_maxims'] ?? null, false);
        $this->trackLegacy('subject_matter.*', $json['subject_matter'] ?? null, false);
        $this->trackLegacy('note', $json['note'] ?? null, true);
        $this->trackLegacy('processed_at', $json['processed_at'] ?? null, true);
        $this->trackLegacy('grading.notes', $json['grading']['notes'] ?? null, true);

        foreach ($json['scholarly_comments'] ?? [] as $comment) {
            $this->trackLegacy(
                'scholarly_comments[].scholar',
                is_array($comment) ? ($comment['scholar'] ?? null) : null,
                true,
            );
        }
    }

    private function trackLegacy(string $key, mixed $payload, bool $handled): void
    {
        $count = $this->payloadCount($payload);
        $this->report['legacy_variants'][$key]['present'] += $count;
        $this->report['legacy_variants'][$key][$handled ? 'handled' : 'skipped'] += $count;
    }

    private function emptyLegacyReport(): array
    {
        return array_fill_keys([
            '_metadata.*',
            'collection_name',
            'local_id',
            'context_ar',
            'context_en',
            'legal_maxims[]',
            'subject_matter.*',
            'note',
            'processed_at',
            'grading.notes',
            'scholarly_comments[].scholar',
        ], ['present' => 0, 'handled' => 0, 'skipped' => 0]);
    }

    private function payloadCount(mixed $payload): int
    {
        if ($payload === null || $payload === '' || $payload === []) {
            return 0;
        }

        return is_array($payload) && array_is_list($payload) ? count($payload) : 1;
    }

    private function scalarValueCount(mixed $payload): int
    {
        if (is_array($payload)) {
            return array_sum(array_map(fn (mixed $part): int => $this->scalarValueCount($part), $payload));
        }

        return $this->optionalText($payload) === null ? 0 : 1;
    }

    private function localized(mixed $arabic, mixed $english): array
    {
        return array_filter([
            'ar' => $this->optionalText($arabic),
            'en' => $this->optionalText($english),
        ], static fn (?string $text): bool => $text !== null);
    }

    private function localizedJson(array $texts): ?string
    {
        $texts = array_filter($texts, fn (mixed $text): bool => $this->optionalText($text) !== null);

        return $texts === []
            ? null
            : json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function optionalText(mixed $text): ?string
    {
        if (! is_scalar($text) || trim((string) $text) === '') {
            return null;
        }

        return (string) $text;
    }

    private function requiredText(mixed $text, string $field): string
    {
        return $this->optionalText($text)
            ?? throw new InvalidArgumentException("Missing populated {$field}");
    }

    private function identifier(mixed $value, int $maxLength = 100): ?string
    {
        $text = $this->optionalText($value);

        return $text === null ? null : mb_substr(mb_strtolower(trim($text)), 0, $maxLength);
    }

    private function hijriYear(mixed $year): ?int
    {
        if (is_int($year)) {
            return $year;
        }
        if (is_scalar($year) && preg_match('/-?\d+/', (string) $year, $matches) === 1) {
            return (int) $matches[0];
        }

        return null;
    }

    private function numericOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function isArabic(string $text): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
    }

    private function personIdentity(array $name): string
    {
        if ($name === []) {
            throw new InvalidArgumentException('Narrator has no populated name');
        }

        $identity = $this->normalizePersonName($name['en'] ?? $name['ar']);

        return match ($identity) {
            'ibn hajar alasqalani' => 'ibn hajar',
            default => $identity,
        };
    }

    private function scholarIdentity(array $name): string
    {
        return $this->personIdentity($name);
    }

    private function normalizePersonName(string $name): string
    {
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

        return preg_replace("/\bal['’](?=\\p{L})/iu", 'al', $normalized) ?? $normalized;
    }

    private function collectionId(): int
    {
        return $this->stableId('collection', $this->collectionCode);
    }

    private function stableId(string $namespace, string $key): int
    {
        return (int) hexdec(substr(hash('sha256', "{$namespace}|{$key}"), 0, 15));
    }

    private function put(string $table, string $key, array $row): void
    {
        $this->rows[$table][$key] = $row;
    }

    private function orderedTables(): array
    {
        $tables = [];

        foreach ($this->rows as $table => $keyedRows) {
            $rows = array_values($keyedRows);
            usort($rows, static fn (array $left, array $right): int => [
                $left['id'] ?? $left['hadith_id'],
                $left['hadith_theme_id'] ?? 0,
            ] <=> [
                $right['id'] ?? $right['hadith_id'],
                $right['hadith_theme_id'] ?? 0,
            ]);
            $tables[$table] = $rows;
        }

        return $tables;
    }

    private function ayahNumbers(mixed $ayah): array
    {
        // Source cases are one verse, an inclusive "start-end" range, or a list of either.
        if (is_array($ayah)) {
            return array_values(array_unique(array_merge(...array_map(
                fn (mixed $part): array => $this->ayahNumbers($part),
                $ayah,
            ))));
        }
        if (is_numeric($ayah)) {
            return [(int) $ayah];
        }
        if (is_string($ayah) && preg_match('/^(\d+)\s*-\s*(\d+)$/', trim($ayah), $match) === 1) {
            return range((int) $match[1], (int) $match[2]);
        }

        return [];
    }
}
