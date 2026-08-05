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
        $collectionId = $this->upsertCollection($code, $collectionData);
        $this->eraseCollectionData($collectionId);
        $files = glob($directory.'/books/*.json') ?: [];
        usort($files, static fn (string $a, string $b): int => strnatcasecmp($a, $b));

        if ($files === []) {
            throw new RuntimeException("{$code}: no book files found");
        }

        $counts = ['books' => 0, 'chapters' => 0, 'hadiths' => 0, 'texts' => 0, 'gradings' => 0, 'narrators' => 0];

        foreach ($files as $sortOrder => $file) {
            DB::transaction(function () use ($file, $bookIndex, $collectionId, $sortOrder, &$counts): void {
                $decoded = $this->json($file);
                $metadata = is_array($decoded) && array_is_list($decoded)
                    ? ($bookIndex[basename($file)] ?? [])
                    : $decoded;
                $records = is_array($decoded) && array_is_list($decoded) ? $decoded : ($decoded['hadiths'] ?? null);

                if (! is_array($records) || ! array_is_list($records)) {
                    throw new RuntimeException("{$file}: missing hadith list");
                }

                $declared = $metadata['hadith_count'] ?? null;
                if (is_int($declared) && $declared !== count($records)) {
                    throw new RuntimeException("{$file}: declared {$declared} hadiths but contains ".count($records));
                }

                $bookId = $this->insertBook($collectionId, $metadata, basename($file, '.json'), $sortOrder + 1);
                [$chapters, $recordChapterIds] = $this->insertChapters($bookId, $metadata, $records);
                $counts['books']++;
                $counts['chapters'] += $chapters;

                foreach (array_chunk($records, 500, true) as $chunk) {
                    $this->insertRecords($collectionId, $bookId, $metadata, $chunk, $recordChapterIds, $counts);
                }
            });
        }

        DB::table('hadith_collections')->where('id', $collectionId)->update([
            'has_books' => DB::table('hadith_books')->where('collection_id', $collectionId)->where('is_synthetic', false)->exists(),
            'has_chapters' => $counts['chapters'] > 0,
            'updated_at' => now(),
        ]);

        return $counts;
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

        return array_merge($expected, ['missing_provenance' => 0]);
    }

    /** @param array<string, mixed> $data */
    private function upsertCollection(string $code, array $data): int
    {
        $title = $this->translations($data['title'] ?? $data['name'] ?? []);
        $now = now();
        $about = is_array($data['about'] ?? null) ? $data['about'] : [];
        DB::table('hadith_collections')->updateOrInsert(['code' => $code], [
            'name' => $this->jsonValue($title !== [] ? $title : ['en' => $code]),
            'author' => null,
            'short_intro' => $this->jsonNullable($this->translations($data['description'] ?? [])),
            'about' => $this->jsonNullable($this->translations($data['about_text'] ?? []) ?: (is_string($about['text'] ?? null) && trim($about['text']) !== '' ? ['en' => trim($about['text'])] : [])),
            'has_books' => true,
            'has_chapters' => true,
            'has_volumes' => false,
            'reference_template' => null,
            'is_published' => true,
            'featured' => in_array($code, ['bukhari', 'muslim'], true),
            'display_order' => array_search($code, array_keys($this->categories), true) ?: 0,
            'source_url' => $data['source_url'] ?? null,
            'about_url' => $data['about_url'] ?? $about['url'] ?? null,
            'source_category' => $this->categories[$code] ?? null,
            'source_metadata' => $this->jsonValue($data),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) DB::table('hadith_collections')->where('code', $code)->value('id');
    }

    /** @param array<string, mixed> $metadata */
    private function insertBook(int $collectionId, array $metadata, string $fallback, int $sortOrder): int
    {
        $number = (string) ($metadata['number'] ?? $metadata['source_book_id'] ?? $fallback);
        $title = $this->translations($metadata['title'] ?? $metadata['name'] ?? []);
        $description = $this->translations(
            $metadata['intro'] ?? $metadata['introductory_text'] ?? $metadata['description'] ?? [],
        );
        $referenceRange = $metadata['source_reference_range'] ?? $metadata['reference_range'] ?? [];

        return (int) DB::table('hadith_books')->insertGetId([
            'collection_id' => $collectionId,
            'book_number' => $number,
            'sort_order' => $sortOrder,
            'title' => $this->jsonValue($title !== [] ? $title : ['en' => $number]),
            'intro' => $this->jsonNullable($description),
            'first_hadith_number' => $referenceRange['first'] ?? $referenceRange['start'] ?? null,
            'last_hadith_number' => $referenceRange['last'] ?? $referenceRange['end'] ?? null,
            'is_synthetic' => $number === 'collection',
            'is_published' => true,
            'source_url' => $metadata['source_url'] ?? null,
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
        $chapters = is_array($metadata['chapters'] ?? null) ? $metadata['chapters'] : [];
        if ($chapters === []) {
            $last = null;
            foreach ($records as $record) {
                $chapter = $record['chapter'] ?? $record['source']['chapter'] ?? null;
                if (is_array($chapter) && $chapter !== [] && $this->jsonValue($chapter) !== $last) {
                    $chapters[] = $chapter;
                    $last = $this->jsonValue($chapter);
                }
            }
        }

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
        $cursor = -1;
        $last = null;
        foreach ($records as $index => $record) {
            $chapter = $record['chapter'] ?? $record['source']['chapter'] ?? null;
            if (! is_array($chapter) || $chapter === []) {
                $recordIds[$index] = null;

                continue;
            }
            $encoded = $this->jsonValue($chapter);
            if ($encoded !== $last) {
                $cursor++;
                $last = $encoded;
            }
            $recordIds[$index] = $ids[$cursor] ?? null;
        }

        return [count($ids), $recordIds];
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
            $sourceReference = $this->sourceReference($source, $references);

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
                'display_grade' => 'unknown',
                'is_published' => true,
                'source_container_id' => $source['container_id'] ?? null,
                'source_reference' => $sourceReference === null ? null : mb_substr($sourceReference, 0, 255),
                'canonical_url' => $references['canonical_url'] ?? $source['reference_url'] ?? null,
                'source_text_sha256' => $source['text_sha256'] ?? null,
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
     * Mirror HadithRepository::recomputeDisplayGrade: an isnad verdict cannot
     * grade the hadith, and conflicting or part-specific claims cannot be
     * collapsed into whichever row happened to be inserted first.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function displayGrade(array $rows): string
    {
        $claims = collect($rows)->where('scope', 'hadith');

        if ($claims->isEmpty() || $claims->contains(fn (array $claim): bool => $claim['part_label'] !== null)) {
            return 'unknown';
        }

        $grades = $claims->pluck('grade')->unique()->values();

        return $grades->count() === 1 ? (string) $grades->first() : 'unknown';
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
                'narrated_by' => $locale === 'en' ? ($source['narration_preface_en'] ?? $segments['narrator_text_en'] ?? null) : null,
                'sanad' => $locale === 'ar' ? ($source['arabic_sanad'] ?? $segments['sanad_ar'] ?? null) : null,
                'matn' => $locale === 'ar' ? ($segments['body_text_ar'] ?? null) : ($segments['body_text_en'] ?? null),
                'translator' => null,
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
        if (! is_array($grading) || ($grading['source_missing'] ?? false) === true) {
            return [];
        }
        $items = [];
        foreach (['en', 'ar'] as $locale) {
            foreach (is_array($grading[$locale] ?? null) ? $grading[$locale] : [] as $item) {
                if (is_array($item)) {
                    $raw = trim((string) ($item['source_text'] ?? $item['grade'] ?? ''));
                    if ($raw !== '') {
                        $items[] = [$locale, $raw, $item['graded_by'] ?? null];
                    }
                }
            }
        }
        if ($items === [] && is_string($grading['displayed'] ?? null)) {
            $items[] = ['en', trim($grading['displayed']), null];
        }
        if ($items === [] && is_string($grading['source_text'] ?? null) && trim($grading['source_text']) !== '') {
            $items[] = ['en', trim($grading['source_text']), $grading['grader'] ?? null];
        }

        $rows = [];
        foreach ($items as [$locale, $raw, $grader]) {
            foreach ($this->grades->normalize($raw, $locale) as $claim) {
                $rows[] = [
                    'hadith_id' => $hadithId,
                    'scholar_id' => is_string($grader) && trim($grader) !== '' ? $this->grader($grader) : null,
                    'grade' => $claim['grade']->value,
                    'scope' => $claim['scope']->value,
                    'part_label' => $claim['part_label'],
                    'authority_rank' => 100,
                    'raw_grade' => $raw,
                    'locale' => $locale,
                    'notes' => null,
                    'source_reference' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $rows;
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

    private function grader(string $name): int
    {
        $key = 'grader:'.Str::lower(trim($name));
        if (isset($this->scholars[$key])) {
            return $this->scholars[$key];
        }
        $slug = Str::slug($name) ?: 'grader-'.sha1($key);
        $existing = DB::table('hadith_scholars')->where('slug', $slug)->value('id');
        if ($existing !== null) {
            return $this->scholars[$key] = (int) $existing;
        }

        return $this->scholars[$key] = (int) DB::table('hadith_scholars')->insertGetId([
            'external_id' => null, 'slug' => $slug, 'name' => $this->jsonValue(['en' => $name]),
            'full_name' => null, 'kunya' => null, 'biography' => null,
            'birth_year_hijri' => null, 'death_year_hijri' => null, 'generation' => null,
            'reliability' => null, 'is_companion' => false, 'sahaba_id' => null, 'source_url' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function hadithNumber(array $source, array $references, string $book, ?int $inBook, int $position): string
    {
        if (isset($source['reference_id']) && trim((string) $source['reference_id']) !== '') {
            return trim((string) $source['reference_id']);
        }
        $url = $references['canonical_url'] ?? $source['reference_url'] ?? null;
        if (is_string($url) && preg_match('/^[^:]+:\/\/.*:([^\/]+)$/', $url, $m) === 1) {
            return $m[1];
        }
        foreach (['reference', 'Reference'] as $key) {
            if (isset($references[$key]) && preg_match('/(?:Hadith|Muslim|Bukhari|Tirmidhi|Dawud|Majah|Nasai|Salihin|Shamail|Mishkat|Adab|Ahmad)\s+([0-9A-Za-z.-]+)\b/u', (string) $references[$key], $m) === 1) {
                return $m[1];
            }
        }

        return $inBook !== null ? $book.'.'.$inBook : $book.'.'.$position;
    }

    private function inBookNumber(array $references): ?int
    {
        foreach ($references as $key => $value) {
            if (is_string($value) && (str_contains(Str::lower((string) $key), 'book') || str_contains(Str::lower((string) $key), 'reference')) && preg_match('/(?:Hadith|Narration)\s+(\d+)/iu', $value, $m) === 1) {
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

    private function sourceReference(array $source, array $references): ?string
    {
        foreach (['reference', 'Reference', 'arabic_reference', 'in_book_reference', 'In-book reference'] as $key) {
            if (isset($references[$key]) && trim((string) $references[$key]) !== '') {
                return trim((string) $references[$key]);
            }
        }
        foreach ($references as $key => $value) {
            if (is_string($value) && trim($value) !== '' && ! str_contains(Str::lower((string) $key), 'url')) {
                return trim($value);
            }
        }

        return isset($source['reference_id']) ? (string) $source['reference_id'] : null;
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
                    $raw = trim((string) ($item['source_text'] ?? $item['grade'] ?? ''));
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
