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
        return [
            'name_ar'        => $this->sanitizeText($collectionData['name_ar'] ?? ''),
            'name_en'        => $this->sanitizeText($collectionData['name_en'] ?? ''),
            'slug_ar'        => \Illuminate\Support\Str::slug($this->sanitizeText($collectionData['name_ar'] ?? ''), '-', 'ar'),
            'slug_en'        => \Illuminate\Support\Str::slug($this->sanitizeText($collectionData['name_en'] ?? '')),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation'   => null,
        ];
    }

    protected function extractBookFromSingle(array $collectionData): array
    {
        $titleAr = $this->sanitizeText($collectionData['book_name_ar'] ?? '');
        $titleEn = $this->sanitizeText($collectionData['book_name_en'] ?? '');

        return [
            'title_ar'         => $titleAr,
            'title_en'         => $titleEn,
            'slug_ar'          => \Illuminate\Support\Str::slug($titleAr, '-', 'ar'),
            'slug_en'          => \Illuminate\Support\Str::slug($titleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];
    }

    protected function extractChapterFromSingle(array $collectionData): ?array
    {
        if (empty($collectionData['chapter_number'])) {
            return null;
        }

        return [
            'chapter_number' => $collectionData['chapter_number'],
            'name'           => json_encode([
                'ar' => $this->sanitizeText($collectionData['chapter_name_ar'] ?? ''),
                'en' => $this->sanitizeText($collectionData['chapter_name_en'] ?? ''),
            ], JSON_UNESCAPED_UNICODE),
            'description'    => null,
            'display_order'  => $collectionData['chapter_number'],
        ];
    }

    protected function extractMetadataCollection(array $collectionMetadata): array
    {
        return [
            'name_ar'        => $this->sanitizeText($collectionMetadata['name_ar'] ?? ''),
            'name_en'        => $this->sanitizeText($collectionMetadata['name_en'] ?? ''),
            'slug_ar'        => \Illuminate\Support\Str::slug($this->sanitizeText($collectionMetadata['name_ar'] ?? ''), '-', 'ar'),
            'slug_en'        => \Illuminate\Support\Str::slug($this->sanitizeText($collectionMetadata['name_en'] ?? '')),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation'   => null,
            'total_hadiths'  => $collectionMetadata['total_hadiths'] ?? 0,
        ];
    }

    protected function extractMetadataBook(array $collectionMetadata): array
    {
        $titleAr = $this->sanitizeText($collectionMetadata['name_ar'] ?? '');
        $titleEn = $this->sanitizeText($collectionMetadata['name_en'] ?? '');

        return [
            'title_ar'         => $titleAr,
            'title_en'         => $titleEn,
            'slug_ar'          => \Illuminate\Support\Str::slug($titleAr, '-', 'ar'),
            'slug_en'          => \Illuminate\Support\Str::slug($titleEn),
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
            'chapter_number' => $chapterId,
            'name'           => json_encode(['ar' => 'الباب', 'en' => 'Chapter'], JSON_UNESCAPED_UNICODE),
            'description'    => null,
            'display_order'  => $chapterId,
        ];
    }

    protected function extractHadithFromSingle(array $jsonData, array $collectionData): array
    {
        $grading = $jsonData['grading'] ?? [];

        return [
            'hadith_number'         => $collectionData['hadith_number'] ?? 0,
            'grading_status'        => $grading['status'] ?? null,
            'grading_agreement'     => $grading['agreement'] ?? null,
            'significance_ar'       => $this->sanitizeText($jsonData['significance_ar'] ?? ''),
            'significance_en'       => $this->sanitizeText($jsonData['significance_en'] ?? ''),
            'historical_context_ar' => $this->sanitizeText($jsonData['historical_context']['occasion_ar'] ?? $jsonData['historical_context']['lesson_ar'] ?? ''),
            'historical_context_en' => $this->sanitizeText($jsonData['historical_context']['occasion_en'] ?? $jsonData['historical_context']['lesson_en'] ?? ''),
            'verification_status'   => $jsonData['verification_status'] ?? null,
            'uncertainty_notes'     => $jsonData['uncertainty_notes'] ?? null,
        ];
    }

    protected function extractTranslationsFromSingle(array $jsonData): array
    {
        $translations = [];
        $hadithText   = $jsonData['hadith_text'] ?? [];

        if (!empty($hadithText['ar'])) {
            $translations[] = ['language_code' => 'ar', 'text' => $this->sanitizeText($hadithText['ar']), 'notes' => null];
        }
        if (!empty($hadithText['ar_notashkeel'])) {
            $translations[] = ['language_code' => 'ar_notashkeel', 'text' => $this->sanitizeText($hadithText['ar_notashkeel']), 'notes' => 'Arabic text without diacritical marks'];
        }
        if (!empty($hadithText['en'])) {
            $translations[] = ['language_code' => 'en', 'text' => $this->sanitizeText($hadithText['en']), 'notes' => null];
        }

        return $translations;
    }

    protected function extractNarrators(array $jsonData): array
    {
        $narrators = $jsonData['sanad']['narrators'] ?? [];
        $result    = [];

        foreach ($narrators as $narrator) {
            $result[] = [
                'name_ar'             => $this->sanitizeText($narrator['name_ar'] ?? ''),
                'name_en'             => $this->sanitizeText($narrator['narrator_en'] ?? ''),
                'full_name_ar'        => $this->sanitizeText($narrator['full_name_ar'] ?? ''),
                'full_name_en'        => null,
                'birth_year_hijri'    => null,
                'death_year_hijri'    => $narrator['death_year_hijri'] ?? null,
                'reliability_grade'   => $narrator['reliability_en'] ?? null,
                'biography_ar'        => $this->sanitizeText($narrator['bio_ar'] ?? ''),
                'biography_en'        => $this->sanitizeText($narrator['bio_en'] ?? ''),
                'position_in_chain'   => $narrator['order'] ?? 0,
                'transmission_method' => $narrator['narrated_from_en'] ?? null,
                'verified'            => $narrator['verified'] ?? false,
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
            'name_ar'          => $this->sanitizeText($companion['name_ar'] ?? ''),
            'name_en'          => $this->sanitizeText($companion['name_en'] ?? ''),
            'full_name_ar'     => $this->sanitizeText($companion['full_name_ar'] ?? ''),
            'full_name_en'     => null,
            'title_ar'         => $this->sanitizeText($companion['title_ar'] ?? ''),
            'title_en'         => $this->sanitizeText($companion['title_en'] ?? ''),
            'birth_year_hijri' => $companion['birth_year_hijri'] ?? null,
            'death_year_hijri' => $companion['death_year_hijri'] ?? null,
            'conversion_year'  => $companion['conversion_year'] ?? null,
            'bio_ar'           => $this->sanitizeText($companion['bio_ar'] ?? ''),
            'bio_en'           => $this->sanitizeText($companion['bio_en'] ?? ''),
            'virtues'          => $this->sanitizeText($companion['virtues'] ?? ''),
            'verified'         => $companion['verified'] ?? false,
        ];
    }

    protected function extractThemes(array $jsonData): array
    {
        $themesEn = $jsonData['themes_en'] ?? [];
        $themesAr = $jsonData['themes_ar'] ?? [];
        $result   = [];
        $maxCount = max(count($themesEn), count($themesAr));

        for ($i = 0; $i < $maxCount; $i++) {
            $result[] = [
                'name_ar'     => $this->sanitizeText($themesAr[$i] ?? ''),
                'name_en'     => $this->sanitizeText($themesEn[$i] ?? ''),
                'description' => null,
            ];
        }

        return $result;
    }

    protected function extractKeywords(array $jsonData): array
    {
        $keywords = $jsonData['keywords'] ?? [];
        $result   = [];

        foreach ($keywords as $keyword) {
            $sanitized = $this->sanitizeText($keyword);
            // Detect language: if contains Arabic characters, it's Arabic
            $isArabic  = (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $sanitized);
            $result[]  = [
                'keyword'       => $sanitized,
                'language_code' => $isArabic ? 'ar' : 'en',
            ];
        }

        return $result;
    }

    protected function extractGrading(array $jsonData): array
    {
        $grading         = $jsonData['grading'] ?? [];
        $authenticatedBy = $grading['authenticated_by'] ?? [];
        $result          = [];

        foreach ($authenticatedBy as $scholarName) {
            $result[] = [
                'scholar_name'  => $this->sanitizeText($scholarName),
                'grading_level' => $grading['status'] ?? null,
                'notes_ar'      => $this->sanitizeText($grading['notes_ar'] ?? ''),
                'notes_en'      => $this->sanitizeText($grading['notes_en'] ?? ''),
            ];
        }

        if (!empty($grading['albani_grade']) && $grading['albani_grade'] !== ($grading['status'] ?? null)) {
            $result[] = [
                'scholar_name'  => 'Shaykh al-Albani',
                'grading_level' => $grading['albani_grade'],
                'notes_ar'      => null,
                'notes_en'      => null,
            ];
        }

        return $result;
    }

    protected function extractScholarlyComments(array $jsonData): array
    {
        $comments = $jsonData['scholarly_comments'] ?? [];
        $result   = [];

        foreach ($comments as $comment) {
            $result[] = [
                'scholar_name_ar'  => $this->sanitizeText($comment['scholar_ar'] ?? ''),
                'scholar_name_en'  => $this->sanitizeText($comment['scholar_en'] ?? ''),
                'commentary_ar'    => $this->sanitizeText($comment['comment_ar'] ?? ''),
                'commentary_en'    => $this->sanitizeText($comment['comment_en'] ?? ''),
                'source_reference' => $this->sanitizeText($comment['source'] ?? ''),
                'verified'         => $comment['verified'] ?? false,
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
                'surah'         => $verse['surah'] ?? 0,
                'surah_name_ar' => $this->sanitizeText($verse['surah_name_ar'] ?? ''),
                'surah_name_en' => $this->sanitizeText($verse['surah_name_en'] ?? ''),
                'ayah'          => $verse['ayah'] ?? 0,
                'text_ar'       => $this->sanitizeText($verse['text_ar'] ?? ''),
                'text_en'       => $this->sanitizeText($verse['text_en'] ?? ''),
                'relevance'     => $this->sanitizeText($verse['relevance'] ?? ''),
            ];
        }

        return $result;
    }

    protected function extractApplications(array $jsonData): array
    {
        $applications = $jsonData['applications'] ?? [];
        $result       = [];

        foreach ($applications as $app) {
            // Handle plain string format (e.g., Malik collection)
            if (is_string($app)) {
                $result[] = [
                    'category_ar'    => '',
                    'category_en'    => '',
                    'description_ar' => '',
                    'description_en' => $this->sanitizeText($app),
                ];
                continue;
            }

            $result[] = [
                'category_ar'    => $this->sanitizeText($app['category_ar'] ?? ''),
                'category_en'    => $this->sanitizeText($app['category_en'] ?? ''),
                'description_ar' => $this->sanitizeText($app['description_ar'] ?? ''),
                'description_en' => $this->sanitizeText($app['description_en'] ?? ''),
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
            'lesson_ar'   => $this->sanitizeText($context['lesson_ar'] ?? ''),
            'lesson_en'   => $this->sanitizeText($context['lesson_en'] ?? ''),
        ];
    }

    protected function extractFiqhRulings(array $jsonData): array
    {
        $legalRulings = $jsonData['legal_rulings'] ?? [];
        $principlesAr = $legalRulings['fiqh_principles_ar'] ?? [];
        $principlesEn = $legalRulings['fiqh_principles_en'] ?? [];
        $result       = [];

        foreach ($principlesAr as $principle) {
            $result[] = ['principle' => $this->sanitizeText($principle), 'language_code' => 'ar'];
        }
        foreach ($principlesEn as $principle) {
            $result[] = ['principle' => $this->sanitizeText($principle), 'language_code' => 'en'];
        }

        return $result;
    }

    // ============================================================================
    // FLAT FORMAT EXTRACTORS (Ahmad-style: single collection string, object-pairs)
    // ============================================================================

    protected function extractCollectionFromFlat(array $jsonData): array
    {
        $meta    = $jsonData['metadata']['collection'] ?? [];
        $nameAr  = $this->sanitizeText($meta['name_ar'] ?? $jsonData['collection'] ?? '');
        $nameEn  = $this->sanitizeText($meta['name_en'] ?? $jsonData['collection'] ?? '');

        return [
            'name_ar'        => $nameAr,
            'name_en'        => $nameEn,
            'slug_ar'        => \Illuminate\Support\Str::slug($nameAr, '-', 'ar'),
            'slug_en'        => \Illuminate\Support\Str::slug($nameEn),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation'   => null,
            'total_hadiths'  => $meta['total_hadiths'] ?? 0,
        ];
    }

    protected function extractBookFromFlat(array $jsonData): array
    {
        $titleAr = $this->sanitizeText($jsonData['book'] ?? '');
        $titleEn = $this->sanitizeText($jsonData['book_en'] ?? '');

        return [
            'title_ar'         => $titleAr,
            'title_en'         => $titleEn,
            'slug_ar'          => \Illuminate\Support\Str::slug($titleAr, '-', 'ar'),
            'slug_en'          => \Illuminate\Support\Str::slug($titleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];
    }

    protected function extractChapterFromFlat(array $jsonData): ?array
    {
        $chapterId = $jsonData['metadata']['chapter_id'] ?? $jsonData['chapter_info'] ?? null;
        $nameAr    = $this->sanitizeText($jsonData['chapter'] ?? '');
        $nameEn    = $this->sanitizeText($jsonData['chapter_en'] ?? '');

        if (empty($chapterId) && empty($nameAr) && empty($nameEn)) {
            return null;
        }

        return [
            'chapter_number' => (int) ($chapterId ?? 0),
            'name'           => json_encode(['ar' => $nameAr, 'en' => $nameEn], JSON_UNESCAPED_UNICODE),
            'description'    => null,
            'display_order'  => (int) ($chapterId ?? 0),
        ];
    }

    protected function extractHadithFromFlat(array $jsonData): array
    {
        $grading = $jsonData['grading'] ?? [];

        return [
            'hadith_number'         => $jsonData['id_in_book'] ?? $jsonData['local_id'] ?? 0,
            'grading_status'        => $grading['status'] ?? null,
            'grading_agreement'     => null,
            'significance_ar'       => '',
            'significance_en'       => '',
            'historical_context_ar' => $this->sanitizeText($jsonData['historical_context']['description_ar'] ?? ''),
            'historical_context_en' => $this->sanitizeText($jsonData['historical_context']['description_en'] ?? ''),
            'verification_status'   => null,
            'uncertainty_notes'     => null,
        ];
    }

    protected function extractNarratorsFromFlat(array $jsonData): array
    {
        $sanad  = $jsonData['sanad'] ?? [];
        $result = [];

        foreach ($sanad as $narrator) {
            $result[] = [
                'name_ar'             => $this->sanitizeText($narrator['narrator'] ?? ''),
                'name_en'             => $this->sanitizeText($narrator['narrator_en'] ?? ''),
                'full_name_ar'        => $this->sanitizeText($narrator['full_name'] ?? ''),
                'full_name_en'        => $this->sanitizeText($narrator['full_name_en'] ?? ''),
                'birth_year_hijri'    => $this->parseHijriYear($narrator['birth_year'] ?? null),
                'death_year_hijri'    => $this->parseHijriYear($narrator['death_year'] ?? null),
                'reliability_grade'   => $this->sanitizeText($narrator['reliability_en'] ?? $narrator['reliability'] ?? ''),
                'biography_ar'        => '',
                'biography_en'        => '',
                'position_in_chain'   => $narrator['order'] ?? 0,
                'transmission_method' => $this->sanitizeText($narrator['verb_en'] ?? $narrator['verb'] ?? ''),
                'verified'            => false,
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
            'name_ar'          => $this->sanitizeText($companion['name'] ?? ''),
            'name_en'          => $this->sanitizeText($companion['name_en'] ?? ''),
            'full_name_ar'     => $this->sanitizeText($companion['full_name'] ?? ''),
            'full_name_en'     => $this->sanitizeText($companion['full_name_en'] ?? ''),
            'title_ar'         => $this->sanitizeText(implode(', ', $titlesAr)),
            'title_en'         => $this->sanitizeText(implode(', ', $titlesEn)),
            'birth_year_hijri' => null,
            'death_year_hijri' => null,
            'conversion_year'  => null,
            'bio_ar'           => '',
            'bio_en'           => '',
            'virtues'          => $this->sanitizeText($this->flattenToString($companion['virtues'] ?? $companion['virtues_en'] ?? '')),
            'verified'         => false,
        ];
    }

    protected function extractThemesFromFlat(array $jsonData): array
    {
        $themes = $jsonData['themes'] ?? [];
        $result = [];

        foreach ($themes as $theme) {
            if (!is_array($theme)) {
                continue;
            }
            $result[] = [
                'name_ar'     => $this->sanitizeText($theme['ar'] ?? ''),
                'name_en'     => $this->sanitizeText($theme['en'] ?? ''),
                'description' => null,
            ];
        }

        return $result;
    }

    protected function extractKeywordsFromFlat(array $jsonData): array
    {
        $keywords = $jsonData['keywords'] ?? [];
        $result   = [];

        foreach ($keywords as $keyword) {
            if (!is_array($keyword)) {
                continue;
            }
            if (!empty($keyword['ar'])) {
                $result[] = ['keyword' => $this->sanitizeText($keyword['ar']), 'language_code' => 'ar'];
            }
            if (!empty($keyword['en'])) {
                $result[] = ['keyword' => $this->sanitizeText($keyword['en']), 'language_code' => 'en'];
            }
        }

        return $result;
    }

    protected function extractGradingFromFlat(array $jsonData): array
    {
        $grading  = $jsonData['grading'] ?? [];
        $scholars = $grading['scholars'] ?? [];
        $result   = [];

        foreach ($scholars as $scholar) {
            $result[] = [
                'scholar_name'  => $this->sanitizeText($scholar['name_en'] ?? $scholar['name'] ?? ''),
                'grading_level' => $this->sanitizeText($scholar['grade_en'] ?? $scholar['grade'] ?? ''),
                'notes_ar'      => $this->sanitizeText($grading['chain_notes'] ?? ''),
                'notes_en'      => '',
            ];
        }

        return $result;
    }

    protected function extractScholarlyCommentsFromFlat(array $jsonData): array
    {
        $comments = $jsonData['scholarly_comments'] ?? [];
        $result   = [];

        foreach ($comments as $comment) {
            $result[] = [
                'scholar_name_ar'  => $this->sanitizeText($comment['scholar'] ?? ''),
                'scholar_name_en'  => $this->sanitizeText($comment['scholar_en'] ?? ''),
                'commentary_ar'    => $this->sanitizeText($comment['comment_ar'] ?? ''),
                'commentary_en'    => $this->sanitizeText($comment['comment_en'] ?? ''),
                'source_reference' => '',
                'verified'         => false,
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
                'surah'         => $verse['surah'] ?? 0,
                'surah_name_ar' => $this->sanitizeText($verse['surah_name'] ?? ''),
                'surah_name_en' => $this->sanitizeText($verse['surah_name_en'] ?? ''),
                'ayah'          => $verse['ayah'] ?? 0,
                'text_ar'       => $this->sanitizeText($verse['text'] ?? ''),
                'text_en'       => $this->sanitizeText($verse['text_en'] ?? ''),
                'relevance'     => $this->sanitizeText($verse['relevance'] ?? ''),
            ];
        }

        return $result;
    }

    protected function extractApplicationsFromFlat(array $jsonData): array
    {
        $applications = $jsonData['practical_applications'] ?? [];
        $result       = [];

        foreach ($applications as $app) {
            if (!is_array($app)) {
                continue;
            }
            $result[] = [
                'category_ar'    => '',
                'category_en'    => '',
                'description_ar' => $this->sanitizeText($app['ar'] ?? ''),
                'description_en' => $this->sanitizeText($app['en'] ?? ''),
            ];
        }

        return $result;
    }

    protected function extractFiqhRulingsFromFlat(array $jsonData): array
    {
        $rulings = $jsonData['fiqh_rulings'] ?? [];
        $result  = [];

        foreach ($rulings as $ruling) {
            if (!is_array($ruling)) {
                continue;
            }
            $result[] = [
                'topic_ar'    => $this->sanitizeText($ruling['topic'] ?? ''),
                'topic_en'    => $this->sanitizeText($ruling['topic_en'] ?? ''),
                'ruling_ar'   => $this->sanitizeText($ruling['ruling'] ?? ''),
                'ruling_en'   => $this->sanitizeText($ruling['ruling_en'] ?? ''),
                'evidence_ar' => $this->sanitizeText($ruling['evidence'] ?? ''),
                'daleel_type' => $this->sanitizeText($ruling['daleel_type_en'] ?? $ruling['daleel_type'] ?? ''),
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
            'lesson_ar'   => $this->sanitizeText($context['significance'] ?? ''),
            'lesson_en'   => $this->sanitizeText($context['significance'] ?? ''),
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
}
