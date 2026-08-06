<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Services\ArabicTextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Imports the lossless, verified docs/hadith-collections contract. */
final class CanonicalHadithImporter
{
    /** @var array<string, int> */
    private array $scholars = [];

    /** @var array<string, string> */
    private array $categories = [];

    /** @var array<string, array<string, list<array<string, mixed>>>> */
    private array $gradingOverlays = [];

    public function __construct(
        private readonly ArabicTextNormalizer $normalizer,
        private readonly GradeNormalizer $grades,
    ) {}

    /** @return list<string> */
    public function collections(string $root): array
    {
        $this->assertRoot($root);
        $directories = glob(rtrim($root, '/').'/*', GLOB_ONLYDIR) ?: [];

        return array_values(array_map(
            'basename',
            array_filter($directories, static fn (string $directory): bool => is_file($directory.'/manifest.json')),
        ));
    }

    /** @return array<string, int> */
    public function importCollection(string $root, string $code): array
    {
        $directory = rtrim($root, '/').'/'.$code;
        $this->assertCollection($directory);
        $this->loadCategories($root);

        $collectionData = $this->json($directory.'/collection.json');
        $bookIndex = $this->bookIndex($directory.'/books.json');
        $files = $this->orderedBookFiles($directory, $bookIndex);

        if ($files === []) {
            throw new RuntimeException("{$code}: no book files found");
        }

        // Validate collection-specific inline claims before deleting any live
        // rows. A stale source substring or unknown reference must make the
        // import fail without disturbing the existing collection.
        $this->preflightCanonicalFiles($directory, $files, $bookIndex);
        $this->validateGradingOverlay($directory, $files);

        return DB::transaction(function () use ($bookIndex, $code, $collectionData, $files): array {
            $collectionId = $this->upsertCollection($code, $collectionData);
            $this->eraseCollectionData($collectionId);
            $counts = ['books' => 0, 'chapters' => 0, 'hadiths' => 0, 'texts' => 0, 'gradings' => 0, 'narrators' => 0];

            foreach ($files as $sortOrder => $file) {
                $decoded = $this->json($file);
                $metadata = is_array($decoded) && array_is_list($decoded)
                    ? ($bookIndex[basename($file)] ?? [])
                    : $decoded;
                $records = is_array($decoded) && array_is_list($decoded) ? $decoded : ($decoded['hadiths'] ?? null);

                if (! is_array($records) || ! array_is_list($records)) {
                    throw new RuntimeException("{$file}: missing hadith list");
                }
                $records = $this->applyGradingOverlay(dirname($file, 2), $records);

                $declared = $metadata['hadith_count'] ?? null;
                if (is_int($declared) && $declared !== count($records)) {
                    throw new RuntimeException("{$file}: declared {$declared} hadiths but contains ".count($records));
                }

                $bookId = $this->insertBook($collectionId, $metadata, $records, basename($file, '.json'), $sortOrder + 1);
                [$chapters, $recordChapterIds] = $this->insertChapters($bookId, $metadata, $records);
                $counts['books']++;
                $counts['chapters'] += $chapters;

                foreach (array_chunk($records, 500, true) as $chunk) {
                    $this->insertRecords($collectionId, $bookId, $metadata, $chunk, $recordChapterIds, $counts);
                }
            }

            DB::table('hadith_collections')->where('id', $collectionId)->update([
                'has_books' => DB::table('hadith_books')->where('collection_id', $collectionId)->where('is_synthetic', false)->exists(),
                'has_chapters' => $counts['chapters'] > 0,
                'updated_at' => now(),
            ]);

            return $counts;
        }, 3);
    }

    public function eraseAll(): void
    {
        // Remove only Hadith-owned morph history, then rely on Hadith FKs.
        DB::table('content_versions')->where('versionable_type', Hadith::class)->delete();
        DB::table('audit_logs')->where('subject_type', Hadith::class)->delete();
        DB::table('hadith_hadiths')->delete();
        DB::table('hadith_chapters')->delete();
        DB::table('hadith_books')->delete();
        DB::table('hadith_import_runs')->delete();
        DB::table('hadith_collections')->delete();
        DB::table('hadith_scholars')->delete();
        $this->scholars = [];
    }

    /** @return array<string, int> */
    public function verifyCollection(string $root, string $code): array
    {
        $directory = rtrim($root, '/').'/'.$code;
        $sourceBooks = 0;
        $sourceHadiths = 0;
        $sourceTexts = 0;
        $sourceNarrators = 0;
        $sourceChapters = 0;
        $sourceGradings = 0;
        $sourceBookTitles = [];

        foreach (glob($directory.'/books/*.json') ?: [] as $file) {
            $decoded = $this->json($file);
            $records = is_array($decoded) && array_is_list($decoded) ? $decoded : ($decoded['hadiths'] ?? []);
            $records = $this->applyGradingOverlay($directory, $records);
            $metadata = is_array($decoded) && array_is_list($decoded)
                ? ($this->bookIndex($directory.'/books.json')[basename($file)] ?? [])
                : $decoded;
            $bookNumber = (string) ($metadata['number'] ?? $metadata['source_book_id'] ?? basename($file, '.json'));
            $sourceBookTitles[$bookNumber] = $this->translations($metadata['title'] ?? $metadata['name'] ?? []);
            $sourceBooks++;
            $sourceHadiths += count($records);
            $sourceChapters += $this->chapterCount($metadata, $records);
            foreach ($records as $record) {
                $sourceTexts += trim((string) ($record['hadith_text']['ar'] ?? '')) !== '' ? 1 : 0;
                $sourceTexts += trim((string) ($record['hadith_text']['en'] ?? '')) !== '' ? 1 : 0;
                $sourceNarrators += count($record['narrators'] ?? []);
                $sourceGradings += $this->gradingClaimCount($record['grading'] ?? []);
            }
        }

        $collectionId = DB::table('hadith_collections')->where('code', $code)->value('id');
        $actual = $collectionId === null ? [] : [
            'books' => DB::table('hadith_books')->where('collection_id', $collectionId)->count(),
            'hadiths' => DB::table('hadith_hadiths')->where('collection_id', $collectionId)->count(),
            'texts' => DB::table('hadith_texts')->join('hadith_hadiths', 'hadith_hadiths.id', '=', 'hadith_texts.hadith_id')->where('hadith_hadiths.collection_id', $collectionId)->count(),
            'narrators' => DB::table('hadith_narrators')->join('hadith_hadiths', 'hadith_hadiths.id', '=', 'hadith_narrators.hadith_id')->where('hadith_hadiths.collection_id', $collectionId)->count(),
            'chapters' => DB::table('hadith_chapters')->join('hadith_books', 'hadith_books.id', '=', 'hadith_chapters.book_id')->where('hadith_books.collection_id', $collectionId)->count(),
            'gradings' => DB::table('hadith_gradings')->join('hadith_hadiths', 'hadith_hadiths.id', '=', 'hadith_gradings.hadith_id')->where('hadith_hadiths.collection_id', $collectionId)->count(),
            'missing_provenance' => DB::table('hadith_hadiths')->where('collection_id', $collectionId)->whereNull('source_metadata')->count(),
        ];

        $expected = ['books' => $sourceBooks, 'chapters' => $sourceChapters, 'hadiths' => $sourceHadiths, 'texts' => $sourceTexts, 'gradings' => $sourceGradings, 'narrators' => $sourceNarrators];
        $manifest = $this->json($directory.'/manifest.json');
        foreach (['output_books' => $sourceBooks, 'output_hadiths' => $sourceHadiths, 'schema_valid_hadiths' => $sourceHadiths] as $field => $value) {
            if (isset($manifest[$field]) && (int) $manifest[$field] !== $value) {
                throw new RuntimeException("{$code}: {$field} does not match the canonical files");
            }
        }
        foreach ($expected as $key => $value) {
            if (($actual[$key] ?? null) !== $value) {
                throw new RuntimeException("{$code}: {$key} source={$value}, database=".($actual[$key] ?? 'missing'));
            }
        }
        if (($actual['missing_provenance'] ?? 1) !== 0) {
            throw new RuntimeException("{$code}: source provenance is missing");
        }

        $databaseBookTitles = DB::table('hadith_books')
            ->where('collection_id', $collectionId)
            ->pluck('title', 'book_number');
        foreach ($sourceBookTitles as $bookNumber => $expectedTitles) {
            $actualTitles = json_decode((string) $databaseBookTitles->get($bookNumber, '{}'), true, flags: JSON_THROW_ON_ERROR);
            foreach ($expectedTitles as $locale => $expectedTitle) {
                if (($actualTitles[$locale] ?? null) !== $expectedTitle) {
                    throw new RuntimeException("{$code}: book {$bookNumber} {$locale} title does not match its canonical source");
                }
            }
        }

        $collectionData = $this->json($directory.'/collection.json');
        $databaseCollection = DB::table('hadith_collections')->where('id', $collectionId)->first([
            'name', 'author', 'short_intro', 'about', 'introduction', 'source_url',
            'about_url', 'introduction_url', 'source_capture_complete', 'source_metadata', 'is_published',
        ]);
        $aboutData = is_array($collectionData['about'] ?? null) ? $collectionData['about'] : [];
        $introductionData = is_array($collectionData['introduction'] ?? null) ? $collectionData['introduction'] : [];
        $expectedAuthor = $this->translations($collectionData['author'] ?? []);
        $expectedAbout = $this->translations($collectionData['about_text'] ?? [])
            ?: (is_string($aboutData['text'] ?? null) && trim($aboutData['text']) !== ''
                ? ['en' => trim($aboutData['text'])]
                : []);
        $expectedIntroduction = $this->translations($collectionData['introduction_text'] ?? [])
            ?: $this->translations($introductionData['text'] ?? []);

        $this->assertCanonicalSame(
            $this->translations($collectionData['title'] ?? $collectionData['name'] ?? []),
            $this->decodedJson($databaseCollection->name),
            "{$code} collection title",
        );
        if ($expectedAuthor !== []) {
            $this->assertCanonicalSame($expectedAuthor, $this->decodedJson($databaseCollection->author), "{$code} collection author");
        }
        $expectedDescription = $this->translations($collectionData['description'] ?? []);
        $this->assertCanonicalSame($expectedDescription === [] ? null : $expectedDescription, $this->decodedJson($databaseCollection->short_intro), "{$code} collection description");
        $this->assertCanonicalSame($expectedAbout === [] ? null : $expectedAbout, $this->decodedJson($databaseCollection->about), "{$code} collection about");
        $this->assertCanonicalSame($expectedIntroduction === [] ? null : $expectedIntroduction, $this->decodedJson($databaseCollection->introduction), "{$code} collection introduction");
        $this->assertCanonicalSame($collectionData['source_url'] ?? null, $databaseCollection->source_url, "{$code} collection source URL");
        $this->assertCanonicalSame($collectionData['about_url'] ?? $aboutData['url'] ?? null, $databaseCollection->about_url, "{$code} collection about URL");
        $this->assertCanonicalSame($collectionData['introduction_url'] ?? $introductionData['url'] ?? null, $databaseCollection->introduction_url, "{$code} collection introduction URL");
        $this->assertCanonicalSame($collectionData, $this->decodedJson($databaseCollection->source_metadata), "{$code} collection source metadata");
        if (is_bool($collectionData['source_capture_complete'] ?? null)) {
            $databaseCaptureComplete = $databaseCollection->source_capture_complete;
            if ($databaseCaptureComplete === null
                || (bool) $databaseCaptureComplete !== $collectionData['source_capture_complete']) {
                throw new RuntimeException("{$code}: source capture completeness does not match its canonical manifest");
            }
        }
        $expectedPublished = is_bool($collectionData['is_published'] ?? null)
            ? $collectionData['is_published']
            : ($collectionData['source_capture_complete'] ?? null) !== false;
        if ((bool) $databaseCollection->is_published !== $expectedPublished) {
            throw new RuntimeException("{$code}: publication status does not match canonical capture completeness");
        }

        $this->verifyRecordParity($directory, (int) $collectionId);

        return array_merge($expected, ['missing_provenance' => 0]);
    }

    /** @param array<string, mixed> $data */
    private function upsertCollection(string $code, array $data): int
    {
        $title = $this->translations($data['title'] ?? $data['name'] ?? []);
        $now = now();
        $about = is_array($data['about'] ?? null) ? $data['about'] : [];
        $introduction = is_array($data['introduction'] ?? null) ? $data['introduction'] : [];
        $introductionUrl = $data['introduction_url'] ?? $introduction['url'] ?? null;
        $introductionText = $this->translations($data['introduction_text'] ?? [])
            ?: $this->translations($introduction['text'] ?? []);
        $existingAuthor = DB::table('hadith_collections')->where('code', $code)->value('author');
        $author = $this->translations($data['author'] ?? []);

        DB::table('hadith_collections')->updateOrInsert(['code' => $code], [
            'name' => $this->jsonValue($title !== [] ? $title : ['en' => $code]),
            // Canonical captures do not all expose author metadata. Preserve a
            // curated existing author instead of erasing it on every reimport.
            'author' => $author !== [] ? $this->jsonValue($author) : $existingAuthor,
            'short_intro' => $this->jsonNullable($this->translations($data['description'] ?? [])),
            'about' => $this->jsonNullable($this->translations($data['about_text'] ?? []) ?: (is_string($about['text'] ?? null) && trim($about['text']) !== '' ? ['en' => trim($about['text'])] : [])),
            'introduction' => $this->jsonNullable($introductionText),
            'has_books' => true,
            'has_chapters' => true,
            'has_volumes' => false,
            'reference_template' => null,
            'is_published' => is_bool($data['is_published'] ?? null)
                ? $data['is_published']
                : ($data['source_capture_complete'] ?? null) !== false,
            'featured' => in_array($code, ['bukhari', 'muslim'], true),
            'display_order' => array_search($code, array_keys($this->categories), true) ?: 0,
            'source_url' => $data['source_url'] ?? null,
            'about_url' => $data['about_url'] ?? $about['url'] ?? null,
            'introduction_url' => $introductionUrl,
            'source_category' => $this->categories[$code] ?? null,
            'source_capture_complete' => is_bool($data['source_capture_complete'] ?? null)
                ? $data['source_capture_complete']
                : null,
            'source_metadata' => $this->jsonValue($data),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) DB::table('hadith_collections')->where('code', $code)->value('id');
    }

    /** @param array<string, mixed> $metadata */
    private function insertBook(int $collectionId, array $metadata, array $records, string $fallback, int $sortOrder): int
    {
        $number = (string) ($metadata['number'] ?? $metadata['source_book_id'] ?? $fallback);
        $title = $this->translations($metadata['title'] ?? $metadata['name'] ?? []);
        $description = $this->translations(
            $metadata['intro'] ?? $metadata['introductory_text'] ?? $metadata['description'] ?? [],
        );
        $referenceRange = $metadata['source_reference_range'] ?? $metadata['reference_range'] ?? [];
        $derivedRange = $this->recordRange($records);

        return (int) DB::table('hadith_books')->insertGetId([
            'collection_id' => $collectionId,
            'book_number' => $number,
            'sort_order' => $sortOrder,
            'title' => $this->jsonValue($title !== [] ? $title : ['en' => $number]),
            'intro' => $this->jsonNullable($description),
            'first_hadith_number' => $referenceRange['first'] ?? $referenceRange['start'] ?? $derivedRange['first'],
            'last_hadith_number' => $referenceRange['last'] ?? $referenceRange['end'] ?? $derivedRange['last'],
            'is_synthetic' => $number === 'collection',
            'is_published' => true,
            'source_url' => $metadata['source_url'] ?? null,
            'source_status' => $this->positiveInt($metadata['source_status'] ?? null),
            'source_error' => $this->nullableText($metadata['source_error'] ?? null),
            'source_metadata' => $this->jsonValue($this->without($metadata, ['hadiths'])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  list<array<string, mixed>>  $records
     * @return array{int, array<int, int|null>}
     */
    private function insertChapters(int $bookId, array $metadata, array $records): array
    {
        $chapters = $this->sourceChapters($metadata, $records);

        $ids = [];
        foreach ($chapters as $position => $chapter) {
            $numberValue = $chapter['number'] ?? '';
            $number = is_array($numberValue) ? (string) ($numberValue['en'] ?? $numberValue['ar'] ?? '') : trim((string) $numberValue, '() ');
            $title = $this->translations($chapter['title'] ?? []);
            $ids[$position] = (int) DB::table('hadith_chapters')->insertGetId([
                'book_id' => $bookId,
                'chapter_number' => mb_substr($number, 0, 20),
                'sort_order' => $position + 1,
                'title' => $this->jsonValue($title !== [] ? $title : ['en' => ($number !== '' ? $number : (string) ($position + 1))]),
                'intro' => $this->jsonNullable($this->translations($chapter['intro'] ?? [])),
                'ending' => $this->jsonNullable($this->translations($chapter['ending'] ?? [])),
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }

        $recordIds = [];
        foreach ($this->recordChapterPositions($chapters, $records, "Book {$bookId}") as $index => $chapterPosition) {
            $recordIds[$index] = $chapterPosition === null ? null : $ids[$chapterPosition];
        }

        return [count($chapters), $recordIds];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, mixed>>
     */
    private function sourceChapters(array $metadata, array $records): array
    {
        $chapters = is_array($metadata['chapters'] ?? null) ? $metadata['chapters'] : [];
        if ($chapters !== []) {
            return array_values($chapters);
        }

        $last = null;
        foreach ($records as $record) {
            $chapter = $record['chapter'] ?? $record['source']['chapter'] ?? null;
            if (is_array($chapter) && $chapter !== [] && $this->jsonValue($chapter) !== $last) {
                $chapters[] = $chapter;
                $last = $this->jsonValue($chapter);
            }
        }

        return $chapters;
    }

    /**
     * @param  list<array<string, mixed>>  $chapters
     * @param  list<array<string, mixed>>  $records
     * @return array<int, int|null>
     */
    private function recordChapterPositions(array $chapters, array $records, string $context): array
    {
        $positions = [];
        $chapterCursor = -1;
        $lastChapter = null;
        foreach ($records as $index => $record) {
            $chapter = $record['chapter'] ?? $record['source']['chapter'] ?? null;
            if (! is_array($chapter) || $chapter === []) {
                $positions[$index] = null;

                continue;
            }

            $encoded = $this->jsonValue($chapter);
            if ($encoded !== $lastChapter) {
                $matchedPosition = null;
                for ($candidate = $chapterCursor + 1, $count = count($chapters); $candidate < $count; $candidate++) {
                    if ($this->jsonValue($chapters[$candidate]) === $encoded) {
                        $matchedPosition = $candidate;

                        break;
                    }
                }

                if ($matchedPosition === null) {
                    throw new RuntimeException("{$context} record ".($index + 1).' references an unknown or out-of-order chapter');
                }

                // Searching forward through the canonical chapter list skips
                // legitimate zero-hadith headings without losing identity when
                // chapter numbers or even complete headings repeat later.
                $chapterCursor = $matchedPosition;
                $lastChapter = $encoded;
            }
            $positions[$index] = $chapterCursor;
        }

        return $positions;
    }

    /** @param array<int, array<string, mixed>> $records @param array<int, int|null> $chapterIds @param array<string, int> $counts */
    private function insertRecords(int $collectionId, int $bookId, array $book, array $records, array $chapterIds, array &$counts): void
    {
        foreach ($records as $index => $record) {
            $source = is_array($record['source'] ?? null) ? $record['source'] : [];
            $text = is_array($record['hadith_text'] ?? null) ? $record['hadith_text'] : [];
            $references = is_array($source['references'] ?? null) ? $source['references'] : [];
            $bookNumber = (string) ($book['number'] ?? $book['source_book_id'] ?? basename((string) ($book['hadith_file'] ?? ''), '.json'));
            $inBook = $this->inBookNumber($references);
            $number = $this->hadithNumber($source, $references, $bookNumber, $inBook, $index + 1);
            $urn = $this->positiveInt($text['urn']['ar'] ?? null);
            $urnEn = $this->positiveInt($text['urn']['en'] ?? null);
            $narrators = is_array($record['narrators'] ?? null) ? $record['narrators'] : [];
            $primaryNarratorId = $narrators === [] ? null : $this->scholar(end($narrators));
            $sourceReference = $this->sourceReference($source, $references, $bookNumber);

            $hadithId = (int) DB::table('hadith_hadiths')->insertGetId([
                'collection_id' => $collectionId,
                'book_id' => $bookId,
                'chapter_id' => $chapterIds[$index] ?? null,
                'primary_narrator_id' => $primaryNarratorId,
                'hadith_number' => mb_substr($number, 0, 50),
                'in_book_number' => $inBook,
                'source_position' => $index + 1,
                'volume_number' => $this->volumeNumber($references),
                'sunnah_urn' => $urn,
                'sunnah_urn_en' => $urnEn,
                'display_grade' => null,
                'is_published' => true,
                'source_container_id' => $source['container_id'] ?? null,
                'source_reference' => $sourceReference === null ? null : mb_substr($sourceReference, 0, 255),
                'source_in_book_reference' => $this->exactReference($references, [
                    'in_book_reference', 'In-book reference', 'book_reference', 'Book reference',
                    'arabic_english_book_reference', 'sunnah_com_reference',
                ]),
                'source_translation_reference' => $this->translationReference($references, $bookNumber),
                'canonical_url' => $this->canonicalUrl($references, $source),
                'source_text_sha256' => $source['text_sha256'] ?? $this->sourceTextHash($text),
                // Keep the source's grading context even when it contains no
                // independent per-hadith verdict. Sahih Muslim explicitly
                // supplies a collection classification, while Bukhari explains
                // that no individual grade is printed on its cards. Neither is
                // a grading claim, but discarding both makes the UI misleadingly
                // say only "Not graded".
                'source_metadata' => $this->jsonValue([
                    'source' => $source,
                    'source_segments' => $record['source_segments'] ?? null,
                    'grading_context' => $this->gradingContext($record['grading'] ?? null),
                    'grading_source' => is_array($record['grading']['source_raw'] ?? null)
                        ? $record['grading']['source_raw']
                        : null,
                ]),
                'source_unavailable_fields' => $this->jsonNullable($record['source_unavailable_fields'] ?? $record['source_missing'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counts['hadiths']++;

            $textRows = $this->textRows($hadithId, $record, $source);
            if ($textRows !== []) {
                DB::table('hadith_texts')->insert($textRows);
                $counts['texts'] += count($textRows);
            }

            $pivot = [];
            foreach ($narrators as $position => $narrator) {
                $pivot[] = [
                    'hadith_id' => $hadithId,
                    'scholar_id' => $this->scholar($narrator),
                    'position' => $position + 1,
                    'display_name_ar' => $narrator['display_text_ar'] ?? $narrator['name_ar'] ?? null,
                    'source_metadata' => $this->jsonValue($narrator),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($pivot !== []) {
                DB::table('hadith_narrators')->insert($pivot);
                $counts['narrators'] += count($pivot);
            }

            $gradingRows = $this->gradingRows($hadithId, $record['grading'] ?? []);
            if ($gradingRows !== []) {
                DB::table('hadith_gradings')->insert($gradingRows);
                $counts['gradings'] += count($gradingRows);
                DB::table('hadith_hadiths')->where('id', $hadithId)->update([
                    'display_grade' => $this->displayGrade($gradingRows),
                ]);
            }
        }
    }

    /**
     * Mirror HadithRepository::recomputeDisplayGrade: isnad and part-specific
     * verdicts do not grade the whole hadith. Exactly one distinct unlabelled
     * hadith-scope grade wins.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function displayGrade(array $rows): ?string
    {
        $claims = collect($rows)
            ->where('scope', 'hadith')
            ->whereNull('part_label');

        if ($claims->isEmpty()) {
            return null;
        }

        $grades = $claims->pluck('grade')->unique()->values();

        $grade = $grades->count() === 1 ? (string) $grades->first() : null;

        return $grade === 'unknown' ? null : $grade;
    }

    /** @return list<array<string, mixed>> */
    private function textRows(int $hadithId, array $record, array $source): array
    {
        $value = $record['hadith_text'] ?? [];
        $segments = $record['source_segments'] ?? [];
        $rows = [];
        foreach (['ar', 'en'] as $locale) {
            $body = trim((string) ($value[$locale] ?? ''));
            if ($body === '') {
                continue;
            }
            $rows[] = [
                'hadith_id' => $hadithId,
                'locale' => $locale,
                'body' => $body,
                'search_body' => trim((string) ($value[$locale.'_search_text'] ?? '')) ?: $this->normalizer->forLocale($body, $locale),
                'narrated_by' => $locale === 'en'
                    ? $this->nullableText($source['narration_preface_en'] ?? $segments['narrator_text_en'] ?? null)
                    : null,
                'sanad' => $locale === 'ar'
                    ? $this->nullableText($source['arabic_sanad'] ?? $segments['sanad_ar'] ?? null)
                    : null,
                'matn' => $this->nullableText(
                    $locale === 'ar' ? ($segments['body_text_ar'] ?? null) : ($segments['body_text_en'] ?? null),
                ),
                'translator' => $locale === 'en'
                    ? $this->nullableText($source['translator_en'] ?? null)
                    : null,
                'source_url' => $source['page_url'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function gradingRows(int $hadithId, mixed $grading): array
    {
        $rows = [];
        foreach ($this->normalizedGradingClaims($grading) as $claim) {
            $rows[] = [
                'hadith_id' => $hadithId,
                'scholar_id' => $claim['grader'] === null ? null : $this->grader($claim['grader'], $claim['locale']),
                'grade' => $claim['grade'],
                'scope' => $claim['scope'],
                'part_label' => $claim['part_label'],
                'authority_rank' => 100,
                'raw_grade' => $claim['raw_grade'],
                'locale' => $claim['locale'],
                'notes' => null,
                'source_reference' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{locale: string, raw_grade: string, grader: ?string, grade: string, scope: string, part_label: ?string}>
     */
    private function normalizedGradingClaims(mixed $grading): array
    {
        if (! is_array($grading) || ($grading['source_missing'] ?? false) === true) {
            return [];
        }

        $items = [];
        foreach (['en', 'ar'] as $locale) {
            foreach (is_array($grading[$locale] ?? null) ? $grading[$locale] : [] as $item) {
                if (is_array($item)) {
                    $raw = trim((string) ($item['source_text'] ?? $item['grade'] ?? ''));
                    if ($raw !== '') {
                        $items[] = [
                            $locale,
                            $raw,
                            $item['graded_by'] ?? null,
                            trim((string) ($item['grade'] ?? $raw)),
                            $item['scope'] ?? null,
                            $item['part_label'] ?? null,
                        ];
                    }
                }
            }
        }
        if ($items === [] && is_string($grading['displayed'] ?? null)) {
            $items[] = ['en', trim($grading['displayed']), null, trim($grading['displayed']), null, null];
        }
        if ($items === [] && is_string($grading['source_text'] ?? null) && trim($grading['source_text']) !== '') {
            $items[] = ['en', trim($grading['source_text']), $grading['grader'] ?? null, trim($grading['source_text']), null, null];
        }

        $claims = [];
        foreach ($items as [$locale, $raw, $grader, $normalizedGrade, $explicitScope, $explicitPartLabel]) {
            foreach ($this->grades->normalize($normalizedGrade, $locale) as $claim) {
                $claims[] = [
                    'locale' => $locale,
                    'raw_grade' => $raw,
                    'grader' => is_string($grader) && trim($grader) !== '' ? trim($grader) : null,
                    'grade' => $claim['grade']->value,
                    'scope' => in_array($explicitScope, ['hadith', 'isnad'], true)
                        ? $explicitScope
                        : $claim['scope']->value,
                    'part_label' => is_string($explicitPartLabel) && trim($explicitPartLabel) !== ''
                        ? trim($explicitPartLabel)
                        : $claim['part_label'],
                ];
            }
        }

        return $claims;
    }

    /**
     * Preserve provenance without turning collection membership into a
     * per-hadith grading row. Only strings asserted by the canonical source are
     * retained; no classification is inferred from a collection's name.
     *
     * @return array<string, mixed>|null
     */
    private function gradingContext(mixed $grading): ?array
    {
        if (! is_array($grading)) {
            return null;
        }

        $context = array_filter([
            'source_missing' => (bool) ($grading['source_missing'] ?? false),
            'collection_classification' => $this->nullableText($grading['collection_classification'] ?? null),
            'source_note' => $this->nullableText($grading['note'] ?? null),
        ], static fn (mixed $value): bool => $value !== null && $value !== false && $value !== '');

        $hasExplicitClaim = $this->gradingClaimCount($grading) > 0;
        $context['per_hadith_status'] = $hasExplicitClaim ? 'explicit' : 'not_provided_by_source';

        return $context;
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function scholar(array $narrator): int
    {
        $external = trim((string) ($narrator['id'] ?? $narrator['source_id'] ?? ''));
        $key = $external !== '' ? 'external:'.$external : 'name:'.sha1($this->jsonValue($narrator));
        if (isset($this->scholars[$key])) {
            return $this->scholars[$key];
        }
        $existing = $external === '' ? null : DB::table('hadith_scholars')->where('external_id', $external)->value('id');
        if ($existing !== null) {
            return $this->scholars[$key] = (int) $existing;
        }
        $name = trim((string) ($narrator['canonical_name_ar'] ?? $narrator['name_ar'] ?? $narrator['display_text_ar'] ?? $key));

        return $this->scholars[$key] = (int) DB::table('hadith_scholars')->insertGetId([
            'external_id' => $external !== '' ? $external : null,
            'slug' => $external !== '' ? 'sunnah-narrator-'.$external : 'narrator-'.sha1($key),
            'name' => $this->jsonValue(['ar' => $name]),
            'full_name' => null, 'kunya' => null, 'biography' => null,
            'birth_year_hijri' => null, 'death_year_hijri' => null,
            'generation' => null, 'reliability' => null, 'is_companion' => false,
            'sahaba_id' => null, 'source_url' => $narrator['source_url'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function grader(string $name, string $locale = 'en'): int
    {
        $identity = $this->graderIdentity($name);
        $slug = $identity['slug'];
        $key = 'grader:'.$slug;
        if (isset($this->scholars[$key])) {
            return $this->scholars[$key];
        }

        $names = $identity['names'] ?? [$locale => trim($name)];
        $existing = DB::table('hadith_scholars')->where('slug', $slug)->first(['id', 'name']);
        if ($existing !== null) {
            $storedNames = $this->decodedJson($existing->name) ?? [];
            $mergedNames = array_merge($storedNames, $names);
            if ($mergedNames !== $storedNames) {
                DB::table('hadith_scholars')->where('id', $existing->id)->update([
                    'name' => $this->jsonValue($mergedNames),
                    'updated_at' => now(),
                ]);
            }

            return $this->scholars[$key] = (int) $existing->id;
        }

        return $this->scholars[$key] = (int) DB::table('hadith_scholars')->insertGetId([
            'external_id' => null, 'slug' => $slug, 'name' => $this->jsonValue($names),
            'full_name' => null, 'kunya' => null, 'biography' => null,
            'birth_year_hijri' => null, 'death_year_hijri' => null, 'generation' => null,
            'reliability' => null, 'is_companion' => false, 'sahaba_id' => null, 'source_url' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array{slug: string, names?: array<string, string>} */
    private function graderIdentity(string $name): array
    {
        $name = trim($name);
        $comparable = Str::lower($name);

        if (in_array($comparable, ['al-albani', 'al albani', 'الألباني'], true)) {
            return [
                'slug' => 'al-albani',
                'names' => ['ar' => 'الألباني', 'en' => 'Al-Albani'],
            ];
        }

        if (in_array($comparable, ['zubair alizai', "zubair `aliza'i", 'زبیر علی زئی'], true)) {
            return [
                'slug' => 'zubair-alizai',
                'names' => ['ar' => 'زبیر علی زئی', 'en' => 'Zubair Alizai', 'ur' => 'زبیر علی زئی'],
            ];
        }

        return ['slug' => Str::slug($name) ?: 'grader-'.sha1('grader:'.$comparable)];
    }

    private function graderSlug(string $name): string
    {
        return $this->graderIdentity($name)['slug'];
    }

    private function hadithNumber(array $source, array $references, string $book, ?int $inBook, int $position): string
    {
        if (isset($source['reference_id']) && trim((string) $source['reference_id']) !== '') {
            return trim((string) $source['reference_id']);
        }
        $url = $this->canonicalUrl($references, $source);
        if (is_string($url) && preg_match('/^[^:]+:\/\/.*:([^\/]+)$/', $url, $m) === 1) {
            return $m[1];
        }
        // Some incomplete source pages (currently Mishkat book 30) expose no
        // canonical permalink/global identity, but do expose a stable unique
        // Sunnah.com in-book reference. Keep that explicit identity instead of
        // treating the source's corrupt Arabic "Hadith 0" line as canonical.
        if ($inBook !== null && $this->exactReference($references, ['sunnah_com_reference']) !== null) {
            return $book.'.'.$inBook;
        }
        foreach (['reference', 'Reference', 'arabic_reference'] as $key) {
            if (isset($references[$key]) && preg_match('/(?:Hadith|Muslim|Bukhari|Tirmidhi|Dawud|Majah|Nasai|Salihin|Shamail|Mishkat|Adab|Ahmad)\s+([0-9A-Za-z.-]+)\b/u', (string) $references[$key], $m) === 1) {
                if ($m[1] !== '0') {
                    return $m[1];
                }
            }
        }

        return $inBook !== null ? $book.'.'.$inBook : $book.'.'.$position;
    }

    private function inBookNumber(array $references): ?int
    {
        foreach (['in_book_reference', 'In-book reference', 'book_reference', 'Book reference', 'sunnah_com_reference'] as $key) {
            $value = $references[$key] ?? null;
            if (is_string($value) && preg_match('/(?:Hadith|Narration)\s+(\d+)/iu', $value, $m) === 1) {
                return (int) $m[1];
            }
        }

        return null;
    }

    private function volumeNumber(array $references): ?string
    {
        foreach ($references as $value) {
            if (is_string($value) && preg_match('/Vol\.\s*(\d+)/iu', $value, $m) === 1) {
                return $m[1];
            }
        }

        return null;
    }

    private function sourceReference(array $source, array $references, ?string $book = null): ?string
    {
        if ($this->canonicalUrl($references, $source) === null) {
            $sunnahReference = $this->exactReference($references, ['sunnah_com_reference']);
            if ($sunnahReference !== null) {
                return $sunnahReference;
            }
        }

        foreach (['reference', 'Reference', 'arabic_reference'] as $key) {
            $value = $references[$key] ?? null;
            if (is_string($value)
                && preg_match('/\d+/u', $value) === 1
                && preg_match('/(?:Hadith|Narration)\s+0\b/iu', $value) !== 1) {
                return trim($value);
            }
        }

        return isset($source['reference_id']) ? (string) $source['reference_id'] : null;
    }

    private function canonicalUrl(array $references, array $source): ?string
    {
        $url = $references['canonical_url'] ?? $source['reference_url'] ?? null;

        return is_string($url) && trim($url) !== '' && ! str_ends_with(trim($url), ':')
            ? trim($url)
            : null;
    }

    private function translationReference(array $references, string $book): ?string
    {
        $reference = $this->exactReference($references, [
            'english_translation', 'usc_msa_web_english_reference',
            'USC-MSA web (English) reference',
        ]);
        if ($reference === null || preg_match('/(?:Hadith|Narration)\s+0\b/iu', $reference) === 1) {
            return null;
        }
        if (preg_match('/Book\s+([0-9A-Za-z.-]+)/iu', $reference, $match) === 1
            && $match[1] !== $book) {
            return null;
        }

        return $reference;
    }

    /**
     * @param  array<string, mixed>  $references
     * @param  list<string>  $keys
     */
    private function exactReference(array $references, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $references[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return mb_substr(trim($value), 0, 255);
            }
        }

        return null;
    }

    /** @param list<array<string, mixed>> $records */
    private function chapterCount(array $metadata, array $records): int
    {
        $chapters = is_array($metadata['chapters'] ?? null) ? $metadata['chapters'] : [];
        if ($chapters !== []) {
            return count($chapters);
        }
        $count = 0;
        $last = null;
        foreach ($records as $record) {
            $chapter = $record['chapter'] ?? $record['source']['chapter'] ?? null;
            if (is_array($chapter) && $chapter !== []) {
                $encoded = $this->jsonValue($chapter);
                if ($encoded !== $last) {
                    $count++;
                    $last = $encoded;
                }
            }
        }

        return $count;
    }

    private function gradingClaimCount(mixed $grading): int
    {
        if (! is_array($grading) || ($grading['source_missing'] ?? false) === true) {
            return 0;
        }
        $raws = [];
        foreach (['en', 'ar'] as $locale) {
            foreach (is_array($grading[$locale] ?? null) ? $grading[$locale] : [] as $item) {
                if (is_array($item)) {
                    $raw = trim((string) ($item['grade'] ?? $item['source_text'] ?? ''));
                    if ($raw !== '') {
                        $raws[] = [$locale, $raw];
                    }
                }
            }
        }
        if ($raws === [] && is_string($grading['displayed'] ?? null) && trim($grading['displayed']) !== '') {
            $raws[] = ['en', trim($grading['displayed'])];
        }
        if ($raws === [] && is_string($grading['source_text'] ?? null) && trim($grading['source_text']) !== '') {
            $raws[] = ['en', trim($grading['source_text'])];
        }
        $count = 0;
        foreach ($raws as [$locale, $raw]) {
            $count += count($this->grades->normalize($raw, $locale));
        }

        return $count;
    }

    private function eraseCollectionData(int $collectionId): void
    {
        $hadithIds = DB::table('hadith_hadiths')->where('collection_id', $collectionId)->pluck('id');
        if ($hadithIds->isNotEmpty()) {
            DB::table('content_versions')->where('versionable_type', Hadith::class)->whereIn('versionable_id', $hadithIds)->delete();
            DB::table('audit_logs')->where('subject_type', Hadith::class)->whereIn('subject_id', $hadithIds)->delete();
        }
        DB::table('hadith_hadiths')->where('collection_id', $collectionId)->delete();
        $bookIds = DB::table('hadith_books')->where('collection_id', $collectionId)->pluck('id');
        if ($bookIds->isNotEmpty()) {
            DB::table('hadith_chapters')->whereIn('book_id', $bookIds)->delete();
        }
        DB::table('hadith_books')->where('collection_id', $collectionId)->delete();
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * Derive the reader-visible range when the capture omitted its book-level
     * summary. This only accepts explicit source references; it never guesses
     * from array positions, which would conceal gaps such as Abdurrazzaq 3.
     *
     * @param  list<array<string, mixed>>  $records
     * @return array{first: ?string, last: ?string}
     */
    private function recordRange(array $records): array
    {
        $numbers = [];

        foreach ($records as $record) {
            $source = is_array($record['source'] ?? null) ? $record['source'] : [];
            $references = is_array($source['references'] ?? null) ? $source['references'] : [];
            $reference = $this->sourceReference($source, $references);

            if (is_string($reference) && preg_match('/(?:Hadith|Narration)\s+([0-9A-Za-z.-]+)/iu', $reference, $matches) === 1) {
                $numbers[] = $matches[1];
            }
        }

        return [
            'first' => $numbers[0] ?? null,
            'last' => $numbers === [] ? null : $numbers[array_key_last($numbers)],
        ];
    }

    /** @param array<string, mixed> $text */
    private function sourceTextHash(array $text): ?string
    {
        foreach (['ar', 'en'] as $locale) {
            $body = trim((string) ($text[$locale] ?? ''));
            if ($body !== '') {
                return hash('sha256', $body);
            }
        }

        return null;
    }

    /** @return array<string, array<string, mixed>> */
    private function bookIndex(string $path): array
    {
        $decoded = $this->json($path);
        $rows = array_is_list($decoded) ? $decoded : ($decoded['books'] ?? []);
        $index = [];
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['hadith_file'])) {
                $index[basename((string) $row['hadith_file'])] = $row;
            }
        }

        return $index;
    }

    /**
     * Follow the source index order exactly. Filename sorting is only a
     * fallback for unindexed files: named books such as `introduction` may
     * intentionally precede numeric books.
     *
     * @param  array<string, array<string, mixed>>  $bookIndex
     * @return list<string>
     */
    private function orderedBookFiles(string $directory, array $bookIndex): array
    {
        $available = [];
        foreach (glob($directory.'/books/*.json') ?: [] as $file) {
            $available[basename($file)] = $file;
        }

        $ordered = [];
        if ($bookIndex !== []) {
            foreach (array_keys($bookIndex) as $filename) {
                if (! isset($available[$filename])) {
                    throw new RuntimeException("{$directory}/books.json references missing file books/{$filename}");
                }
                $ordered[] = $available[$filename];
                unset($available[$filename]);
            }

            if ($available !== []) {
                throw new RuntimeException("{$directory}/books contains files absent from books.json");
            }

            return $ordered;
        }

        $remaining = array_values($available);
        usort($remaining, static fn (string $a, string $b): int => strnatcasecmp($a, $b));

        return $remaining;
    }

    private function verifyRecordParity(string $directory, int $collectionId): void
    {
        $bookIndex = $this->bookIndex($directory.'/books.json');

        foreach ($this->orderedBookFiles($directory, $bookIndex) as $sortOrder => $file) {
            $decoded = $this->json($file);
            $metadata = is_array($decoded) && array_is_list($decoded)
                ? ($bookIndex[basename($file)] ?? [])
                : $decoded;
            $records = is_array($decoded) && array_is_list($decoded) ? $decoded : ($decoded['hadiths'] ?? []);
            $records = $this->applyGradingOverlay($directory, $records);
            $bookNumber = (string) ($metadata['number'] ?? $metadata['source_book_id'] ?? basename($file, '.json'));
            $book = DB::table('hadith_books')
                ->where('collection_id', $collectionId)
                ->where('book_number', $bookNumber)
                ->first([
                    'id', 'sort_order', 'title', 'intro', 'first_hadith_number',
                    'last_hadith_number', 'is_synthetic', 'is_published', 'source_url',
                    'source_status', 'source_error', 'source_metadata',
                ]);

            if ($book === null) {
                throw new RuntimeException("Book {$bookNumber}: missing from database");
            }

            $this->assertCanonicalSame($sortOrder + 1, (int) $book->sort_order, "Book {$bookNumber} sort order");
            $bookTitle = $this->translations($metadata['title'] ?? $metadata['name'] ?? []);
            $this->assertCanonicalSame(
                $bookTitle !== [] ? $bookTitle : ['en' => $bookNumber],
                $this->decodedJson($book->title),
                "Book {$bookNumber} title",
            );
            $bookIntro = $this->translations($metadata['intro'] ?? $metadata['introductory_text'] ?? $metadata['description'] ?? []);
            $this->assertCanonicalSame($bookIntro === [] ? null : $bookIntro, $this->decodedJson($book->intro), "Book {$bookNumber} intro");
            $referenceRange = $metadata['source_reference_range'] ?? $metadata['reference_range'] ?? [];
            $derivedRange = $this->recordRange($records);
            $expectedFirst = $referenceRange['first'] ?? $referenceRange['start'] ?? $derivedRange['first'];
            $expectedLast = $referenceRange['last'] ?? $referenceRange['end'] ?? $derivedRange['last'];
            $this->assertCanonicalSame($expectedFirst === null ? null : (string) $expectedFirst, $book->first_hadith_number, "Book {$bookNumber} first reference");
            $this->assertCanonicalSame($expectedLast === null ? null : (string) $expectedLast, $book->last_hadith_number, "Book {$bookNumber} last reference");
            $this->assertCanonicalSame($bookNumber === 'collection', (bool) $book->is_synthetic, "Book {$bookNumber} synthetic status");
            $this->assertCanonicalSame(true, (bool) $book->is_published, "Book {$bookNumber} publication status");
            $this->assertCanonicalSame($metadata['source_url'] ?? null, $book->source_url, "Book {$bookNumber} source URL");
            $this->assertCanonicalSame(
                $this->positiveInt($metadata['source_status'] ?? null),
                $book->source_status === null ? null : (int) $book->source_status,
                "Book {$bookNumber} source status",
            );
            $this->assertCanonicalSame($this->nullableText($metadata['source_error'] ?? null), $book->source_error, "Book {$bookNumber} source error");
            $this->assertCanonicalSame($this->without($metadata, ['hadiths']), $this->decodedJson($book->source_metadata), "Book {$bookNumber} source metadata");

            $sourceChapters = $this->sourceChapters($metadata, $records);
            $chapterPositions = $this->recordChapterPositions($sourceChapters, $records, "Book {$bookNumber}");
            $databaseChapters = DB::table('hadith_chapters')
                ->where('book_id', $book->id)
                ->orderBy('sort_order')
                ->get(['chapter_number', 'sort_order', 'title', 'intro', 'ending']);
            $this->assertCanonicalSame(count($sourceChapters), $databaseChapters->count(), "Book {$bookNumber} chapter count");
            foreach ($sourceChapters as $chapterPosition => $chapter) {
                $actualChapter = $databaseChapters->get($chapterPosition);
                $numberValue = $chapter['number'] ?? '';
                $number = is_array($numberValue)
                    ? (string) ($numberValue['en'] ?? $numberValue['ar'] ?? '')
                    : trim((string) $numberValue, '() ');
                $title = $this->translations($chapter['title'] ?? []);

                $this->assertCanonicalSame($chapterPosition + 1, (int) $actualChapter->sort_order, "Book {$bookNumber} chapter ".($chapterPosition + 1).' order');
                $this->assertCanonicalSame(mb_substr($number, 0, 20), $actualChapter->chapter_number, "Book {$bookNumber} chapter ".($chapterPosition + 1).' number');
                $this->assertCanonicalSame(
                    $title !== [] ? $title : ['en' => ($number !== '' ? $number : (string) ($chapterPosition + 1))],
                    $this->decodedJson($actualChapter->title),
                    "Book {$bookNumber} chapter ".($chapterPosition + 1).' title',
                );
                $expectedIntro = $this->translations($chapter['intro'] ?? []);
                $expectedEnding = $this->translations($chapter['ending'] ?? []);
                $this->assertCanonicalSame($expectedIntro === [] ? null : $expectedIntro, $this->decodedJson($actualChapter->intro), "Book {$bookNumber} chapter ".($chapterPosition + 1).' intro');
                $this->assertCanonicalSame($expectedEnding === [] ? null : $expectedEnding, $this->decodedJson($actualChapter->ending), "Book {$bookNumber} chapter ".($chapterPosition + 1).' ending');
            }

            $databaseRecords = DB::table('hadith_hadiths as h')
                ->leftJoin('hadith_chapters as ch', 'ch.id', '=', 'h.chapter_id')
                ->where('h.book_id', $book->id)
                ->get([
                    'h.id', 'h.hadith_number', 'h.in_book_number', 'h.source_position',
                    'h.volume_number', 'h.sunnah_urn', 'h.sunnah_urn_en',
                    'h.source_container_id', 'h.source_reference', 'h.source_in_book_reference',
                    'h.source_translation_reference', 'h.canonical_url',
                    'h.source_text_sha256', 'h.display_grade', 'ch.sort_order as chapter_sort_order',
                ])
                ->keyBy('source_position');
            $databaseTexts = DB::table('hadith_texts')
                ->whereIn('hadith_id', $databaseRecords->pluck('id'))
                ->get(['hadith_id', 'locale', 'body', 'search_body', 'narrated_by', 'sanad', 'matn', 'translator', 'source_url'])
                ->groupBy('hadith_id');
            $databaseGradings = DB::table('hadith_gradings as g')
                ->leftJoin('hadith_scholars as s', 's.id', '=', 'g.scholar_id')
                ->whereIn('g.hadith_id', $databaseRecords->pluck('id'))
                ->get(['g.hadith_id', 'g.locale', 'g.raw_grade', 'g.grade', 'g.scope', 'g.part_label', 's.slug as grader_slug'])
                ->groupBy('hadith_id');

            foreach ($records as $index => $record) {
                $position = $index + 1;
                $actual = $databaseRecords->get($position);
                if ($actual === null) {
                    throw new RuntimeException("Book {$bookNumber} record {$position}: missing from database");
                }

                $source = is_array($record['source'] ?? null) ? $record['source'] : [];
                $text = is_array($record['hadith_text'] ?? null) ? $record['hadith_text'] : [];
                $references = is_array($source['references'] ?? null) ? $source['references'] : [];
                $inBook = $this->inBookNumber($references);
                $reference = $this->sourceReference($source, $references, $bookNumber);
                $context = $reference ?? "Book {$bookNumber} record {$position}";
                $expectedChapterSortOrder = $chapterPositions[$index] === null
                    ? null
                    : $chapterPositions[$index] + 1;

                $this->assertCanonicalSame(
                    $this->hadithNumber($source, $references, $bookNumber, $inBook, $position),
                    $actual->hadith_number,
                    "{$context} hadith number",
                );
                $this->assertCanonicalSame($inBook, $actual->in_book_number === null ? null : (int) $actual->in_book_number, "{$context} in-book number");
                $this->assertCanonicalSame($this->volumeNumber($references), $actual->volume_number, "{$context} volume");
                $this->assertCanonicalSame($this->positiveInt($text['urn']['ar'] ?? null), $actual->sunnah_urn === null ? null : (int) $actual->sunnah_urn, "{$context} Arabic URN");
                $this->assertCanonicalSame($this->positiveInt($text['urn']['en'] ?? null), $actual->sunnah_urn_en === null ? null : (int) $actual->sunnah_urn_en, "{$context} English URN");
                $this->assertCanonicalSame($source['container_id'] ?? null, $actual->source_container_id, "{$context} container ID");
                $this->assertCanonicalSame($reference, $actual->source_reference, "{$context} source reference");
                $this->assertCanonicalSame($this->exactReference($references, [
                    'in_book_reference', 'In-book reference', 'book_reference', 'Book reference',
                    'arabic_english_book_reference', 'sunnah_com_reference',
                ]), $actual->source_in_book_reference, "{$context} in-book reference");
                $this->assertCanonicalSame($this->translationReference($references, $bookNumber), $actual->source_translation_reference, "{$context} translation reference");
                $this->assertCanonicalSame($this->canonicalUrl($references, $source), $actual->canonical_url, "{$context} canonical URL");
                $this->assertCanonicalSame($source['text_sha256'] ?? $this->sourceTextHash($text), $actual->source_text_sha256, "{$context} source text hash");
                $this->assertCanonicalSame(
                    $expectedChapterSortOrder,
                    $actual->chapter_sort_order === null ? null : (int) $actual->chapter_sort_order,
                    "{$context} chapter identity",
                );

                $expectedTexts = collect($this->textRows(0, $record, $source))->keyBy('locale');
                $actualTexts = $databaseTexts->get($actual->id, collect())->keyBy('locale');
                foreach (['ar', 'en'] as $locale) {
                    $expectedText = $expectedTexts->get($locale);
                    $actualText = $actualTexts->get($locale);
                    if (($expectedText === null) !== ($actualText === null)) {
                        throw new RuntimeException("{$context} {$locale} text presence does not match its canonical source");
                    }
                    if ($expectedText === null || $actualText === null) {
                        continue;
                    }
                    foreach (['body', 'search_body', 'narrated_by', 'sanad', 'matn', 'translator', 'source_url'] as $field) {
                        $this->assertCanonicalSame($expectedText[$field], $actualText->{$field}, "{$context} {$locale} {$field}");
                    }
                }

                $expectedClaims = collect($this->normalizedGradingClaims($record['grading'] ?? []))
                    ->map(fn (array $claim): array => [
                        'locale' => $claim['locale'],
                        'raw_grade' => $claim['raw_grade'],
                        'grade' => $claim['grade'],
                        'scope' => $claim['scope'],
                        'part_label' => $claim['part_label'],
                        'grader_slug' => $claim['grader'] === null ? null : $this->graderSlug($claim['grader']),
                    ])
                    ->sortBy(static fn (array $claim): string => json_encode($claim, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->values()
                    ->all();
                $actualClaims = $databaseGradings->get($actual->id, collect())
                    ->map(static fn (object $claim): array => [
                        'locale' => $claim->locale,
                        'raw_grade' => $claim->raw_grade,
                        'grade' => $claim->grade,
                        'scope' => $claim->scope,
                        'part_label' => $claim->part_label,
                        'grader_slug' => $claim->grader_slug,
                    ])
                    ->sortBy(static fn (array $claim): string => json_encode($claim, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->values()
                    ->all();
                $this->assertCanonicalSame($expectedClaims, $actualClaims, "{$context} grading claims");

                $expectedDisplayGrade = $this->displayGrade(array_map(
                    static fn (array $claim): array => [
                        'grade' => $claim['grade'],
                        'scope' => $claim['scope'],
                        'part_label' => $claim['part_label'],
                    ],
                    $expectedClaims,
                ));
                $this->assertCanonicalSame($expectedDisplayGrade, $actual->display_grade, "{$context} display grade");
            }
        }
    }

    private function assertCanonicalSame(mixed $expected, mixed $actual, string $context): void
    {
        if ($this->comparableValue($expected) !== $this->comparableValue($actual)) {
            throw new RuntimeException("{$context} does not match its canonical source");
        }
    }

    private function comparableValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->comparableValue($item);
        }
        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    /** @return array<string, mixed>|null */
    private function decodedJson(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, mixed>>
     */
    private function applyGradingOverlay(string $directory, array $records): array
    {
        $overlay = $this->gradingOverlay($directory);
        if ($overlay === []) {
            return $records;
        }

        $inline = ! is_file($directory.'/grading-overrides.json');

        foreach ($records as &$record) {
            $source = is_array($record['source'] ?? null) ? $record['source'] : [];
            $references = is_array($source['references'] ?? null) ? $source['references'] : [];
            $key = $this->gradingOverlayKey($overlay, $source, $references);
            if ($key === null) {
                continue;
            }
            $claims = $overlay[$key];
            $originalGrading = is_array($record['grading'] ?? null) ? $record['grading'] : [];

            $grading = [
                'ar' => [],
                'en' => [],
                'source_missing' => false,
                'source_location' => $inline ? 'inline_hadith_body' : 'source_grade_table',
                'source_raw' => $originalGrading,
            ];
            foreach ($claims as $claim) {
                $locale = $claim['locale'] ?? null;
                $sourceText = $claim['source_text'] ?? null;
                if (! in_array($locale, ['ar', 'en'], true)
                    || ! is_string($sourceText)
                    || $sourceText === ''
                    || ! $this->gradingSourceContains($record, $originalGrading, $locale, $sourceText, $inline)) {
                    throw new RuntimeException("{$key}: grading claim does not match its {$locale} source text");
                }

                if (! in_array($claim['grade'] ?? null, ['sahih', 'hasan', 'daif', 'mawdu'], true)
                    || ! in_array($claim['scope'] ?? null, ['hadith', 'isnad'], true)) {
                    throw new RuntimeException("{$key}: grading claim has an invalid grade or scope");
                }

                $grading[$locale][] = [
                    'grade' => $claim['grade'],
                    'graded_by' => is_string($claim['grader'] ?? null) ? $claim['grader'] : null,
                    'source_text' => $sourceText,
                    'scope' => $claim['scope'],
                    'part_label' => $claim['part_label'] ?? null,
                ];
            }
            $grading['source_text'] = collect($claims)->pluck('source_text')->implode(' | ');
            $record['grading'] = $grading;
            if (is_array($record['source_missing'] ?? null)) {
                $record['source_missing']['grading'] = false;
            }
        }
        unset($record);

        return $records;
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function gradingOverlay(string $directory): array
    {
        if (array_key_exists($directory, $this->gradingOverlays)) {
            return $this->gradingOverlays[$directory];
        }

        $path = is_file($directory.'/grading-overrides.json')
            ? $directory.'/grading-overrides.json'
            : $directory.'/inline-gradings.json';
        if (! is_file($path)) {
            return $this->gradingOverlays[$directory] = [];
        }

        $overlay = $this->json($path);
        foreach ($overlay as $reference => $claims) {
            if (! is_string($reference) || ! is_array($claims) || ! array_is_list($claims)) {
                throw new RuntimeException("{$path}: invalid grading mapping");
            }
        }

        /** @var array<string, list<array<string, mixed>>> $overlay */
        return $this->gradingOverlays[$directory] = $overlay;
    }

    /**
     * @param  list<string>  $files
     */
    private function validateGradingOverlay(string $directory, array $files): void
    {
        $remaining = $this->gradingOverlay($directory);
        if ($remaining === []) {
            return;
        }

        $seen = [];
        foreach ($files as $file) {
            $decoded = $this->json($file);
            $records = is_array($decoded) && array_is_list($decoded)
                ? $decoded
                : ($decoded['hadiths'] ?? []);
            foreach ($records as $record) {
                $source = is_array($record['source'] ?? null) ? $record['source'] : [];
                $references = is_array($source['references'] ?? null) ? $source['references'] : [];
                $key = $this->gradingOverlayKey($remaining, $source, $references)
                    ?? $this->gradingOverlayKey($this->gradingOverlay($directory), $source, $references);
                if ($key !== null) {
                    if (isset($seen[$key])) {
                        throw new RuntimeException("{$directory}: grading mapping {$key} matches multiple canonical records");
                    }
                    $seen[$key] = true;
                    unset($remaining[$key]);
                }
            }

            // This performs exact source-text and enum validation without
            // changing the canonical files.
            $this->applyGradingOverlay($directory, $records);
        }

        if ($remaining !== []) {
            throw new RuntimeException(
                $directory.'/grading overlay references missing canonical records: '
                .count($remaining).' unmatched; first keys: '
                .implode(', ', array_slice(array_keys($remaining), 0, 5)),
            );
        }
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $overlay
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $references
     */
    private function gradingOverlayKey(array $overlay, array $source, array $references): ?string
    {
        $reference = $this->sourceReference($source, $references);
        if (is_string($reference) && array_key_exists($reference, $overlay)) {
            return $reference;
        }

        // A correction overlay may need to identify a malformed raw reference
        // that is intentionally *not* promoted to the public source_reference
        // (Mishkat book 30 is the current example). The raw value remains
        // losslessly available in source metadata and is safe as an overlay key
        // when it is unique.
        foreach (['reference', 'Reference', 'arabic_reference'] as $field) {
            $rawReference = $references[$field] ?? null;
            if (is_string($rawReference) && array_key_exists($rawReference, $overlay)) {
                return $rawReference;
            }
        }

        $container = trim((string) ($source['container_id'] ?? ''));
        $containerKey = $container === '' ? null : 'container:'.$container;

        return $containerKey !== null && array_key_exists($containerKey, $overlay)
            ? $containerKey
            : null;
    }

    /** @param array<string, mixed> $record @param array<string, mixed> $grading */
    private function gradingSourceContains(array $record, array $grading, string $locale, string $sourceText, bool $inline): bool
    {
        if ($inline) {
            return str_contains((string) ($record['hadith_text'][$locale] ?? ''), $sourceText);
        }

        foreach (is_array($grading[$locale] ?? null) ? $grading[$locale] : [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach (['source_text', 'grade'] as $field) {
                if (is_string($item[$field] ?? null) && str_contains($item[$field], $sourceText)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validate the complete file set before the atomic replacement starts.
     * This is especially important for legacy indexes without `hadith_file`:
     * the manifest must still catch a deleted book JSON.
     *
     * @param  list<string>  $files
     * @param  array<string, array<string, mixed>>  $bookIndex
     */
    private function preflightCanonicalFiles(string $directory, array $files, array $bookIndex): void
    {
        $books = 0;
        $hadiths = 0;

        foreach ($files as $file) {
            $decoded = $this->json($file);
            $metadata = is_array($decoded) && array_is_list($decoded)
                ? ($bookIndex[basename($file)] ?? [])
                : $decoded;
            $records = is_array($decoded) && array_is_list($decoded)
                ? $decoded
                : ($decoded['hadiths'] ?? null);
            if (! is_array($records) || ! array_is_list($records)) {
                throw new RuntimeException("{$file}: missing hadith list");
            }

            $declared = $metadata['hadith_count'] ?? null;
            if (is_int($declared) && $declared !== count($records)) {
                throw new RuntimeException("{$file}: declared {$declared} hadiths but contains ".count($records));
            }

            $chapters = $this->sourceChapters($metadata, $records);
            $this->recordChapterPositions($chapters, $records, $file);
            $books++;
            $hadiths += count($records);
        }

        $manifest = $this->json($directory.'/manifest.json');
        foreach (['output_books' => $books, 'output_hadiths' => $hadiths, 'schema_valid_hadiths' => $hadiths] as $field => $actual) {
            if (isset($manifest[$field]) && (int) $manifest[$field] !== $actual) {
                throw new RuntimeException("{$directory}: {$field} does not match the canonical file set");
            }
        }
    }

    private function loadCategories(string $root): void
    {
        if ($this->categories !== []) {
            return;
        }
        $payload = $this->json(rtrim($root, '/').'/collections.json');
        foreach ($payload['collections'] ?? [] as $collection) {
            $this->categories[(string) $collection['slug']] = (string) ($collection['category'] ?? '');
        }
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Missing source file: {$path}");
        }
        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException("Invalid JSON object: {$path}");
        }

        return $decoded;
    }

    private function assertRoot(string $root): void
    {
        if (! is_file(rtrim($root, '/').'/collections.json')) {
            throw new RuntimeException("Invalid canonical source root: {$root}");
        }
    }

    private function assertCollection(string $directory): void
    {
        foreach (['collection.json', 'books.json', 'manifest.json'] as $file) {
            if (! is_file($directory.'/'.$file)) {
                throw new RuntimeException("{$directory}: missing {$file}");
            }
        }
    }

    /** @return array<string, string> */
    private function translations(mixed $value): array
    {
        if (is_string($value)) {
            return trim($value) === '' ? [] : ['en' => trim($value)];
        }

        if (! is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $locale => $text) {
            if (is_string($text) && trim($text) !== '') {
                $out[(string) $locale] = trim($text);
            }
        }

        return $out;
    }

    private function jsonValue(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function jsonNullable(mixed $value): ?string
    {
        return $value === [] || $value === null ? null : $this->jsonValue($value);
    }

    /** @param list<string> $keys */
    private function without(array $value, array $keys): array
    {
        foreach ($keys as $key) {
            unset($value[$key]);
        }

        return $value;
    }
}
