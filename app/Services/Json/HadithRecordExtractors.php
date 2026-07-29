<?php

declare(strict_types=1);

namespace App\Services\Json;

/**
 * HadithRecordExtractors
 *
 * Trait providing per-field extraction methods for the single-hadith JSON format.
 * Used by HadithJsonTransformer to build normalized database records.
 *
 * Every method in this trait depends on sanitizeText() being available in the
 * using class (provided by HadithJsonTransformer).
 */
trait HadithRecordExtractors
{
    protected function extractCollectionFromSingle(array $collectionData): array
    {
        $nameEn = $this->sanitizeText($collectionData['name_en'] ?? '');

        return [
            'name_ar' => $this->sanitizeText($collectionData['name_ar'] ?? ''),
            'name_en' => $nameEn,
            'slug_ar' => \Illuminate\Support\Str::slug($this->sanitizeText($collectionData['name_ar'] ?? ''), '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($nameEn),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation' => null,
            'canonical_code' => $this->canonicalCollectionCode($nameEn),
            'source_slug' => $this->sourceCollectionSlug($nameEn),
        ];
    }

    protected function extractBookFromSingle(array $collectionData): array
    {
        $titleAr = $this->sanitizeText($collectionData['book_name_ar'] ?? '');
        $titleEn = $this->sanitizeText($collectionData['book_name_en'] ?? '');

        return [
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'book_number' => isset($collectionData['book_number']) ? (string) $collectionData['book_number'] : null,
            'slug_ar' => \Illuminate\Support\Str::slug($titleAr, '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($titleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];
    }

    protected function extractChapterFromSingle(array $collectionData): ?array
    {
        if (empty($collectionData['chapter_number'])) {
            return null;
        }

        $chapterNumber = (string) $collectionData['chapter_number'];

        return [
            'chapter_number' => $chapterNumber,
            'name' => json_encode([
                'ar' => $this->sanitizeText($collectionData['chapter_name_ar'] ?? ''),
                'en' => $this->sanitizeText($collectionData['chapter_name_en'] ?? ''),
            ], JSON_UNESCAPED_UNICODE),
            'description' => null,
            'display_order' => $this->chapterDisplayOrder($chapterNumber),
        ];
    }

    protected function extractMetadataCollection(array $collectionMetadata): array
    {
        $nameEn = $this->sanitizeText($collectionMetadata['name_en'] ?? '');

        return [
            'name_ar' => $this->sanitizeText($collectionMetadata['name_ar'] ?? ''),
            'name_en' => $nameEn,
            'slug_ar' => \Illuminate\Support\Str::slug($this->sanitizeText($collectionMetadata['name_ar'] ?? ''), '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($nameEn),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation' => null,
            'total_hadiths' => $collectionMetadata['total_hadiths'] ?? 0,
            'canonical_code' => $this->canonicalCollectionCode($nameEn),
            'source_slug' => $this->sourceCollectionSlug($nameEn),
        ];
    }

    protected function extractMetadataBook(array $collectionMetadata): array
    {
        $titleAr = $this->sanitizeText($collectionMetadata['name_ar'] ?? '');
        $titleEn = $this->sanitizeText($collectionMetadata['name_en'] ?? '');

        return [
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'book_number' => null,
            'slug_ar' => \Illuminate\Support\Str::slug($titleAr, '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($titleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];
    }

    protected function extractMetadataChapter(array $metadata): ?array
    {
        $chapterId = $metadata['chapter_id'] ?? 0;
        if (empty($chapterId)) {
            return null;
        }

        return [
            'chapter_number' => (string) $chapterId,
            'name' => json_encode(['ar' => 'الباب', 'en' => 'Chapter'], JSON_UNESCAPED_UNICODE),
            'description' => null,
            'display_order' => $this->chapterDisplayOrder((string) $chapterId),
        ];
    }

    protected function extractHadithFromSingle(array $jsonData, array $collectionData): array
    {
        $grading = $jsonData['grading'] ?? [];
        $hadithNumber = $collectionData['hadith_number']
            ?? $jsonData['metadata']['sunnah_reference']['hadith_number']
            ?? $jsonData['metadata']['sunnah_reference']['book_hadith_number']
            ?? $jsonData['metadata']['id_in_book']
            ?? $jsonData['id_in_book']
            ?? 0;

        return [
            'hadith_number' => (string) $hadithNumber,
            'local_source_id' => (string) ($jsonData['metadata']['id'] ?? $jsonData['local_id'] ?? ''),
            'source_url' => $this->primarySourceUrl($jsonData, $collectionData),
            'sunnah_urn_ar' => $this->sunnahUrn($jsonData, 'ar'),
            'sunnah_urn_en' => $this->sunnahUrn($jsonData, 'en'),
            'source_verification_method' => $this->sourceVerificationMethod($jsonData, $collectionData),
            'grading_status' => $grading['status'] ?? null,
            'grading_agreement' => $grading['agreement'] ?? null,
            'significance_ar' => $this->sanitizeText($jsonData['significance_ar'] ?? ''),
            'significance_en' => $this->sanitizeText($jsonData['significance_en'] ?? ''),
            'historical_context_ar' => $this->sanitizeText($jsonData['historical_context']['occasion_ar'] ?? $jsonData['historical_context']['lesson_ar'] ?? ''),
            'historical_context_en' => $this->sanitizeText($jsonData['historical_context']['occasion_en'] ?? $jsonData['historical_context']['lesson_en'] ?? ''),
            'verification_status' => $jsonData['verification_status'] ?? null,
            'uncertainty_notes' => $jsonData['uncertainty_notes'] ?? null,
        ];
    }

    protected function extractTranslationsFromSingle(array $jsonData): array
    {
        $translations = [];
        $hadithText = $jsonData['hadith_text'] ?? [];
        $sourceUrl = $this->primarySourceUrl($jsonData);
        $verified = ($jsonData['verification_status'] ?? null) === 'source_verified';
        $urnAr = $this->sunnahUrn($jsonData, 'ar');
        $urnEn = $this->sunnahUrn($jsonData, 'en');

        $arabicText = $hadithText['ar'] ?? $hadithText['ar_notashkeel'] ?? null;
        if (! empty($arabicText)) {
            $translations[] = [
                'language_code' => 'ar',
                'text' => $this->sanitizeText($arabicText),
                'search_text' => ! empty($hadithText['ar_notashkeel'])
                    ? $this->sanitizeText($hadithText['ar_notashkeel'])
                    : null,
                'notes' => null,
                'source_url' => $sourceUrl,
                'urn' => $urnAr,
                'verified' => $verified,
                'is_primary' => true,
            ];
        }
        if (! empty($hadithText['en'])) {
            $translations[] = ['language_code' => 'en', 'text' => $this->sanitizeText($hadithText['en']), 'notes' => null, 'source_url' => $sourceUrl, 'urn' => $urnEn, 'verified' => $verified, 'is_primary' => empty($hadithText['ar'])];
        }

        return $translations;
    }

    protected function extractNarrators(array $jsonData): array
    {
        $narrators = $jsonData['sanad']['narrators'] ?? [];
        $result = [];

        foreach ($narrators as $narrator) {
            $result[] = [
                'name_ar' => $this->sanitizeText($narrator['name_ar'] ?? ''),
                'name_en' => $this->sanitizeText($narrator['narrator_en'] ?? ''),
                'full_name_ar' => $this->sanitizeText($narrator['full_name_ar'] ?? ''),
                'full_name_en' => null,
                'birth_year_hijri' => null,
                'death_year_hijri' => $narrator['death_year_hijri'] ?? null,
                'reliability_grade' => $narrator['reliability_en'] ?? null,
                'biography_ar' => $this->sanitizeText($narrator['bio_ar'] ?? ''),
                'biography_en' => $this->sanitizeText($narrator['bio_en'] ?? ''),
                'position_in_chain' => $narrator['order'] ?? 0,
                'transmission_method' => $narrator['narrated_from_en'] ?? null,
                'verified' => $narrator['verified'] ?? false,
                'source_system' => ! empty($narrator['source_narrator_id']) ? 'sunnah' : null,
                'source_narrator_id' => $narrator['source_narrator_id'] ?? null,
            ];
        }

        return $result;
    }

    protected function extractCompanion(array $jsonData): ?array
    {
        $companion = $jsonData['companion'] ?? null;
        if (empty($companion)) {
            return null;
        }

        return [
            'name_ar' => $this->sanitizeText($companion['name_ar'] ?? ''),
            'name_en' => $this->sanitizeText($companion['name_en'] ?? ''),
            'full_name_ar' => $this->sanitizeText($companion['full_name_ar'] ?? ''),
            'full_name_en' => null,
            'title_ar' => $this->sanitizeText($companion['title_ar'] ?? ''),
            'title_en' => $this->sanitizeText($companion['title_en'] ?? ''),
            'birth_year_hijri' => $companion['birth_year_hijri'] ?? null,
            'death_year_hijri' => $companion['death_year_hijri'] ?? null,
            'conversion_year' => $companion['conversion_year'] ?? null,
            'bio_ar' => $this->sanitizeText($companion['bio_ar'] ?? ''),
            'bio_en' => $this->sanitizeText($companion['bio_en'] ?? ''),
            'virtues' => $this->sanitizeText($companion['virtues'] ?? ''),
            'verified' => $companion['verified'] ?? false,
        ];
    }

    protected function extractThemes(array $jsonData): array
    {
        $themesEn = $jsonData['themes_en'] ?? [];
        $themesAr = $jsonData['themes_ar'] ?? [];
        $result = [];

        if (empty($themesEn) && empty($themesAr) && ! empty($jsonData['themes']) && is_array($jsonData['themes'])) {
            foreach ($jsonData['themes'] as $theme) {
                if (is_array($theme)) {
                    $result[] = [
                        'name_ar' => $this->sanitizeText($theme['ar'] ?? $theme['name_ar'] ?? ''),
                        'name_en' => $this->sanitizeText($theme['en'] ?? $theme['name_en'] ?? ''),
                        'description' => null,
                    ];
                } elseif (is_scalar($theme)) {
                    $result[] = [
                        'name_ar' => '',
                        'name_en' => $this->sanitizeText($theme),
                        'description' => null,
                    ];
                }
            }

            return $result;
        }

        $maxCount = max(count($themesEn), count($themesAr));

        for ($i = 0; $i < $maxCount; $i++) {
            $result[] = [
                'name_ar' => $this->sanitizeText($themesAr[$i] ?? ''),
                'name_en' => $this->sanitizeText($themesEn[$i] ?? ''),
                'description' => null,
            ];
        }

        return $result;
    }

    protected function extractKeywords(array $jsonData): array
    {
        $keywords = $jsonData['keywords'] ?? [];
        $result = [];

        foreach ($keywords as $keyword) {
            if (is_array($keyword)) {
                if (! empty($keyword['ar'])) {
                    $result[] = ['keyword' => $this->sanitizeText($keyword['ar']), 'language_code' => 'ar'];
                }
                if (! empty($keyword['en'])) {
                    $result[] = ['keyword' => $this->sanitizeText($keyword['en']), 'language_code' => 'en'];
                }

                continue;
            }

            $sanitized = $this->sanitizeText($keyword);
            // Detect language: if contains Arabic characters, it's Arabic
            $isArabic = (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $sanitized);
            $result[] = [
                'keyword' => $sanitized,
                'language_code' => $isArabic ? 'ar' : 'en',
            ];
        }

        return $result;
    }

    protected function extractGrading(array $jsonData): array
    {
        $grading = $jsonData['grading'] ?? [];
        $authenticatedBy = $grading['authenticated_by'] ?? [];
        $result = [];

        foreach ($authenticatedBy as $scholarName) {
            $result[] = [
                'scholar_name' => $this->sanitizeText($scholarName),
                'grading_level' => $grading['status'] ?? null,
                'notes_ar' => $this->sanitizeText($grading['notes_ar'] ?? ''),
                'notes_en' => $this->sanitizeText($grading['notes_en'] ?? ''),
            ];
        }

        if (! empty($grading['albani_grade']) && $grading['albani_grade'] !== ($grading['status'] ?? null)) {
            $result[] = [
                'scholar_name' => 'Shaykh al-Albani',
                'grading_level' => $grading['albani_grade'],
                'notes_ar' => null,
                'notes_en' => null,
            ];
        }

        return $result;
    }

    protected function extractScholarlyComments(array $jsonData): array
    {
        $comments = $jsonData['scholarly_comments'] ?? [];
        $result = [];

        foreach ($comments as $comment) {
            $result[] = [
                'scholar_name_ar' => $this->sanitizeText($comment['scholar_ar'] ?? ''),
                'scholar_name_en' => $this->sanitizeText($comment['scholar_en'] ?? ''),
                'commentary_ar' => $this->sanitizeText($comment['comment_ar'] ?? ''),
                'commentary_en' => $this->sanitizeText($comment['comment_en'] ?? ''),
                'source_reference' => $this->sanitizeText($comment['source'] ?? ''),
                'verified' => $comment['verified'] ?? false,
            ];
        }

        return $result;
    }

    protected function extractRelatedVerses(array $jsonData): array
    {
        $verses = $jsonData['related_verses'] ?? [];
        $result = [];

        foreach ($verses as $verse) {
            $result[] = [
                'surah' => $verse['surah'] ?? 0,
                'surah_name_ar' => $this->sanitizeText($verse['surah_name_ar'] ?? ''),
                'surah_name_en' => $this->sanitizeText($verse['surah_name_en'] ?? ''),
                'ayah' => $verse['ayah'] ?? 0,
                'text_ar' => $this->sanitizeText($verse['text_ar'] ?? ''),
                'text_en' => $this->sanitizeText($verse['text_en'] ?? ''),
                'relevance' => $this->sanitizeText($verse['relevance'] ?? ''),
            ];
        }

        return $result;
    }

    protected function extractHistoricalContext(array $jsonData): ?array
    {
        $context = $jsonData['historical_context'] ?? null;
        if (empty($context)) {
            return null;
        }

        return [
            'occasion_ar' => $this->sanitizeText($context['occasion_ar'] ?? ''),
            'occasion_en' => $this->sanitizeText($context['occasion_en'] ?? ''),
            'lesson_ar' => $this->sanitizeText($context['lesson_ar'] ?? ''),
            'lesson_en' => $this->sanitizeText($context['lesson_en'] ?? ''),
        ];
    }

    // ============================================================================
    // FLAT FORMAT EXTRACTORS (Ahmad-style: single collection string, object-pairs)
    // ============================================================================

    protected function extractCollectionFromFlat(array $jsonData): array
    {
        $meta = $jsonData['metadata']['collection'] ?? [];
        $nameAr = $this->sanitizeText($meta['name_ar'] ?? $jsonData['collection'] ?? '');
        $nameEn = $this->sanitizeText($meta['name_en'] ?? $jsonData['collection'] ?? '');

        return [
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'slug_ar' => \Illuminate\Support\Str::slug($nameAr, '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($nameEn),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation' => null,
            'total_hadiths' => $meta['total_hadiths'] ?? 0,
            'canonical_code' => $this->canonicalCollectionCode($nameEn),
            'source_slug' => $this->sourceCollectionSlug($nameEn),
        ];
    }

    protected function extractBookFromFlat(array $jsonData): array
    {
        $titleAr = $this->sanitizeText($jsonData['book'] ?? '');
        $titleEn = $this->sanitizeText($jsonData['book_en'] ?? '');

        return [
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'book_number' => isset($jsonData['metadata']['sunnah_reference']['book_number']) ? (string) $jsonData['metadata']['sunnah_reference']['book_number'] : null,
            'slug_ar' => \Illuminate\Support\Str::slug($titleAr, '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($titleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];
    }

    protected function extractChapterFromFlat(array $jsonData): ?array
    {
        $chapterId = $jsonData['metadata']['chapter_id'] ?? $jsonData['chapter_info'] ?? null;
        $nameAr = $this->sanitizeText($jsonData['chapter'] ?? '');
        $nameEn = $this->sanitizeText($jsonData['chapter_en'] ?? '');

        if (empty($chapterId) && empty($nameAr) && empty($nameEn)) {
            return null;
        }

        return [
            'chapter_number' => (string) ($chapterId ?? 0),
            'name' => json_encode(['ar' => $nameAr, 'en' => $nameEn], JSON_UNESCAPED_UNICODE),
            'description' => null,
            'display_order' => $this->chapterDisplayOrder((string) ($chapterId ?? 0)),
        ];
    }

    protected function extractHadithFromFlat(array $jsonData): array
    {
        $grading = $jsonData['grading'] ?? [];

        return [
            'hadith_number' => $jsonData['id_in_book'] ?? $jsonData['local_id'] ?? 0,
            'local_source_id' => (string) ($jsonData['metadata']['id'] ?? $jsonData['local_id'] ?? ''),
            'source_url' => $this->primarySourceUrl($jsonData),
            'sunnah_urn_ar' => $this->sunnahUrn($jsonData, 'ar'),
            'sunnah_urn_en' => $this->sunnahUrn($jsonData, 'en'),
            'source_verification_method' => $this->sourceVerificationMethod($jsonData),
            'grading_status' => $grading['status'] ?? null,
            'grading_agreement' => null,
            'significance_ar' => '',
            'significance_en' => '',
            'historical_context_ar' => $this->sanitizeText($jsonData['historical_context']['description_ar'] ?? ''),
            'historical_context_en' => $this->sanitizeText($jsonData['historical_context']['description_en'] ?? ''),
            'verification_status' => null,
            'uncertainty_notes' => null,
        ];
    }

    protected function extractNarratorsFromFlat(array $jsonData): array
    {
        $sanad = $jsonData['sanad'] ?? [];
        $result = [];

        foreach ($sanad as $narrator) {
            $result[] = [
                'name_ar' => $this->sanitizeText($narrator['narrator'] ?? ''),
                'name_en' => $this->sanitizeText($narrator['narrator_en'] ?? ''),
                'full_name_ar' => $this->sanitizeText($narrator['full_name'] ?? ''),
                'full_name_en' => $this->sanitizeText($narrator['full_name_en'] ?? ''),
                'birth_year_hijri' => $this->parseHijriYear($narrator['birth_year'] ?? null),
                'death_year_hijri' => $this->parseHijriYear($narrator['death_year'] ?? null),
                'reliability_grade' => $this->sanitizeText($narrator['reliability_en'] ?? $narrator['reliability'] ?? ''),
                'biography_ar' => '',
                'biography_en' => '',
                'position_in_chain' => $narrator['order'] ?? 0,
                'transmission_method' => $this->sanitizeText($narrator['verb_en'] ?? $narrator['verb'] ?? ''),
                'verified' => false,
                'source_system' => null,
                'source_narrator_id' => null,
            ];
        }

        return $result;
    }

    protected function extractCompanionFromFlat(array $jsonData): ?array
    {
        $companion = $jsonData['companion'] ?? null;
        if (empty($companion)) {
            return null;
        }

        $titlesAr = $companion['titles'] ?? [];
        $titlesEn = $companion['titles_en'] ?? [];

        return [
            'name_ar' => $this->sanitizeText($companion['name'] ?? ''),
            'name_en' => $this->sanitizeText($companion['name_en'] ?? ''),
            'full_name_ar' => $this->sanitizeText($companion['full_name'] ?? ''),
            'full_name_en' => $this->sanitizeText($companion['full_name_en'] ?? ''),
            'title_ar' => $this->sanitizeText(implode(', ', $titlesAr)),
            'title_en' => $this->sanitizeText(implode(', ', $titlesEn)),
            'birth_year_hijri' => null,
            'death_year_hijri' => null,
            'conversion_year' => null,
            'bio_ar' => '',
            'bio_en' => '',
            'virtues' => $this->sanitizeText($this->flattenToString($companion['virtues'] ?? $companion['virtues_en'] ?? '')),
            'verified' => false,
        ];
    }

    protected function extractThemesFromFlat(array $jsonData): array
    {
        $themes = $jsonData['themes'] ?? [];
        $result = [];

        foreach ($themes as $theme) {
            if (! is_array($theme)) {
                continue;
            }
            $result[] = [
                'name_ar' => $this->sanitizeText($theme['ar'] ?? ''),
                'name_en' => $this->sanitizeText($theme['en'] ?? ''),
                'description' => null,
            ];
        }

        return $result;
    }

    protected function extractKeywordsFromFlat(array $jsonData): array
    {
        $keywords = $jsonData['keywords'] ?? [];
        $result = [];

        foreach ($keywords as $keyword) {
            if (! is_array($keyword)) {
                continue;
            }
            if (! empty($keyword['ar'])) {
                $result[] = ['keyword' => $this->sanitizeText($keyword['ar']), 'language_code' => 'ar'];
            }
            if (! empty($keyword['en'])) {
                $result[] = ['keyword' => $this->sanitizeText($keyword['en']), 'language_code' => 'en'];
            }
        }

        return $result;
    }

    protected function extractGradingFromFlat(array $jsonData): array
    {
        $grading = $jsonData['grading'] ?? [];
        $scholars = $grading['scholars'] ?? [];
        $result = [];

        foreach ($scholars as $scholar) {
            $result[] = [
                'scholar_name' => $this->sanitizeText($scholar['name_en'] ?? $scholar['name'] ?? ''),
                'grading_level' => $this->sanitizeText($scholar['grade_en'] ?? $scholar['grade'] ?? ''),
                'notes_ar' => $this->sanitizeText($grading['chain_notes'] ?? ''),
                'notes_en' => '',
            ];
        }

        return $result;
    }

    protected function extractScholarlyCommentsFromFlat(array $jsonData): array
    {
        $comments = $jsonData['scholarly_comments'] ?? [];
        $result = [];

        foreach ($comments as $comment) {
            $result[] = [
                'scholar_name_ar' => $this->sanitizeText($comment['scholar'] ?? ''),
                'scholar_name_en' => $this->sanitizeText($comment['scholar_en'] ?? ''),
                'commentary_ar' => $this->sanitizeText($comment['comment_ar'] ?? ''),
                'commentary_en' => $this->sanitizeText($comment['comment_en'] ?? ''),
                'source_reference' => '',
                'verified' => false,
            ];
        }

        return $result;
    }

    protected function extractRelatedVersesFromFlat(array $jsonData): array
    {
        $verses = $jsonData['related_verses'] ?? [];
        $result = [];

        foreach ($verses as $verse) {
            $result[] = [
                'surah' => $verse['surah'] ?? 0,
                'surah_name_ar' => $this->sanitizeText($verse['surah_name'] ?? ''),
                'surah_name_en' => $this->sanitizeText($verse['surah_name_en'] ?? ''),
                'ayah' => $verse['ayah'] ?? 0,
                'text_ar' => $this->sanitizeText($verse['text'] ?? ''),
                'text_en' => $this->sanitizeText($verse['text_en'] ?? ''),
                'relevance' => $this->sanitizeText($verse['relevance'] ?? ''),
            ];
        }

        return $result;
    }

    protected function extractHistoricalContextFromFlat(array $jsonData): ?array
    {
        $context = $jsonData['historical_context'] ?? null;
        if (empty($context)) {
            return null;
        }

        return [
            'occasion_ar' => $this->sanitizeText($context['description_ar'] ?? $context['event'] ?? ''),
            'occasion_en' => $this->sanitizeText($context['description_en'] ?? $context['event_en'] ?? ''),
            'lesson_ar' => $this->sanitizeText($context['significance'] ?? ''),
            'lesson_en' => $this->sanitizeText($context['significance'] ?? ''),
        ];
    }

    /**
     * Parse a Hijri year string like "197 AH" or "129 AH" into an integer.
     * Returns null if the input is empty or unparseable.
     */
    private function parseHijriYear(?string $yearStr): ?int
    {
        if (empty($yearStr)) {
            return null;
        }

        if (preg_match('/(\d+)/', $yearStr, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Flatten a value to string — handles arrays by joining with ', '
     */
    private function flattenToString(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_filter(array_map(fn ($v) => is_string($v) ? $v : '', $value)));
        }

        return is_string($value) ? $value : '';
    }

    private function chapterDisplayOrder(string $chapterNumber): int
    {
        if (preg_match('/^\d+/', $chapterNumber, $matches)) {
            return (int) $matches[0];
        }

        return 0;
    }

    protected function extractSources(array $jsonData): array
    {
        $metadata = $jsonData['metadata'] ?? [];
        $collection = $metadata['collection'] ?? [];
        $primary = $this->primaryCollectionData($jsonData);
        $sourceUrl = $this->primarySourceUrl($jsonData, $primary);
        $reference = $primary['hadith_number'] ?? $metadata['id_in_book'] ?? null;
        $sourceVerification = $jsonData['source_verification']['sunnah'] ?? [];

        if (empty($sourceUrl) && empty($reference)) {
            return [];
        }

        return [[
            'local_source_id' => (string) ($metadata['id'] ?? $jsonData['local_id'] ?? ''),
            'source_type' => str_starts_with((string) $sourceUrl, 'https://sunnah.com') ? 'sunnah_web' : 'source',
            'title' => $this->sanitizeText($collection['name_en'] ?? $primary['name_en'] ?? ''),
            'author' => $this->sanitizeText($collection['author_en'] ?? ''),
            'reference' => (string) $reference,
            'url' => $sourceUrl,
            'sunnah_urn' => $this->sunnahUrn($jsonData, 'ar') ?? $this->sunnahUrn($jsonData, 'en'),
            'verification_method' => $sourceVerification['status'] ?? $this->sourceVerificationMethod($jsonData, $primary),
            'verified_at' => ($jsonData['verification_status'] ?? null) === 'source_verified' ? now() : null,
        ]];
    }

    private function primaryCollectionData(array $jsonData): array
    {
        $metadataCollection = ($jsonData['metadata'] ?? [])['collection'] ?? [];
        foreach ($jsonData['collections'] ?? [] as $collection) {
            if (
                (! empty($metadataCollection['name_en']) && ($collection['name_en'] ?? null) === $metadataCollection['name_en'])
                || (! empty($metadataCollection['name_ar']) && ($collection['name_ar'] ?? null) === $metadataCollection['name_ar'])
            ) {
                return $collection;
            }
        }

        return ($jsonData['collections'] ?? [])[0] ?? [];
    }

    private function primarySourceUrl(array $jsonData, ?array $collectionData = null): ?string
    {
        $collectionData ??= $this->primaryCollectionData($jsonData);
        $sourceUrl = $collectionData['source_url'] ?? ($jsonData['source_verification']['sunnah']['url'] ?? null);

        return is_string($sourceUrl) && $sourceUrl !== '' ? $sourceUrl : null;
    }

    private function sourceVerificationMethod(array $jsonData, ?array $collectionData = null): ?string
    {
        if (! empty($jsonData['source_verification']['sunnah']['status'])) {
            return (string) $jsonData['source_verification']['sunnah']['status'];
        }

        $collectionData ??= $this->primaryCollectionData($jsonData);
        if (! empty($collectionData['source_verified'])) {
            return 'source_verified';
        }

        return null;
    }

    private function sunnahUrn(array $jsonData, string $language): ?int
    {
        $reference = $jsonData['metadata']['sunnah_reference'] ?? [];
        $key = $language === 'en' ? 'urn_en' : 'urn';
        $value = $reference[$key] ?? null;

        if ($value === null && $language === 'ar') {
            $value = $reference['sunnah_urn'] ?? null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function canonicalCollectionCode(string $nameEn): ?string
    {
        $slug = $this->sourceCollectionSlug($nameEn);

        return $slug ? str_replace('-', '_', $slug) : null;
    }

    private function sourceCollectionSlug(string $nameEn): ?string
    {
        $known = [
            'Sahih al-Bukhari' => 'bukhari',
            'Sahih Muslim' => 'muslim',
            'Sunan Abi Dawud' => 'abudawud',
            "Jami' al-Tirmidhi" => 'tirmidhi',
            "Sunan al-Nasa'i" => 'nasai',
            'Sunan Ibn Majah' => 'ibnmajah',
            'Muwatta Malik' => 'malik',
            'Sunan al-Darimi' => 'darimi',
            'Bulugh al-Maram' => 'bulugh',
            'Riyad as-Salihin' => 'riyadussalihin',
            'Mishkat al-Masabih' => 'mishkat',
            "Shama'il Muhammadiyah" => 'shamail',
            'Al-Adab Al-Mufrad' => 'adab',
        ];

        return $known[$nameEn] ?? (\Illuminate\Support\Str::slug($nameEn) ?: null);
    }
}
