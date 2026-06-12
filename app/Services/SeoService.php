<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SeoException;
use App\Exceptions\SeoGenerationException;
use App\Models\Article;
use App\Services\Dtos\SeoMetadata;
use App\Services\Dtos\SeoValidationResult;
use Illuminate\Support\Facades\Storage;

/**
 * SEO Service
 *
 * Comprehensive SEO metadata generation service for The Last Messenger platform.
 * Handles Schema.org JSON-LD, OpenGraph, Twitter Cards, and standard meta tags.
 */
class SeoService
{
    /**
     * SEO configuration settings
     */
    private const SEO_SETTINGS = [
        'title_min_length' => 30,
        'title_max_length' => 60,
        'title_optimal_length' => 55,

        'description_min_length' => 120,
        'description_max_length' => 160,
        'description_optimal_length' => 155,

        'keywords_min_count' => 3,
        'keywords_max_count' => 5,

        'min_content_length' => 300,
        'min_images' => 1,
        'og_image_width' => 1200,
        'og_image_height' => 630,

        'organization_name' => 'The Last Messenger',
        'organization_url' => 'https://thelastmessenger.com',
        'organization_logo' => 'https://thelastmessenger.com/logo.png',
    ];

    /**
     * Supported locales
     */
    private const SUPPORTED_LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    /**
     * Locale to OpenGraph locale code mapping
     */
    private const LOCALE_CODES = [
        'ar' => 'ar_SA',
        'en' => 'en_US',
        'fr' => 'fr_FR',
        'tr' => 'tr_TR',
        'ur' => 'ur_PK',
    ];

    /**
     * Generate complete SEO metadata for article
     *
     * @param  string  $locale  Language code (default: 'ar')
     *
     * @throws SeoException
     */
    public function generateSeoData(Article $article, string $locale = 'ar'): SeoMetadata
    {
        $this->validateLocale($locale);

        // Generate all components
        $title = $this->getContent($article, 'seo_title', $locale) ?: $this->getContent($article, 'title', $locale);
        $description = $this->getContent($article, 'seo_description', $locale) ?: $this->generateExcerpt(
            $this->getContent($article, 'excerpt', $locale) ?: $this->getContent($article, 'content', $locale),
            self::SEO_SETTINGS['description_optimal_length']
        );
        $keywords = $article->seo_keywords ?? '';
        $canonicalUrl = $article->getUrl();
        $featuredImage = $this->getFeaturedImageUrl($article);

        return new SeoMetadata(
            title: $this->truncateText($title, self::SEO_SETTINGS['title_optimal_length']),
            description: $this->truncateText($description, self::SEO_SETTINGS['description_optimal_length']),
            keywords: $keywords,
            canonicalUrl: $canonicalUrl,
            featuredImage: $featuredImage,
            openGraph: $this->generateOpenGraphTags($article, $locale),
            twitterCard: $this->generateTwitterCardTags($article, $locale),
            jsonLd: $this->generateJsonLdArray($article),
            metaTags: $this->generateMetaTags($article, $locale),
            locale: $locale
        );
    }

    /**
     * Generate Schema.org JSON-LD structured data
     *
     * @param  string  $type  Schema type (default: 'Article')
     * @return string JSON-LD string
     *
     * @throws SeoGenerationException
     */
    public function generateJsonLd(Article $article, string $type = 'Article'): string
    {
        try {
            $data = $this->generateJsonLdArray($article, $type);

            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            throw SeoGenerationException::jsonLdFailed($e->getMessage());
        }
    }

    /**
     * Generate JSON-LD as array
     *
     * @param  string  $type  Schema type
     * @return array<string, mixed>
     */
    private function generateJsonLdArray(Article $article, string $type = 'Article'): array
    {
        $locale = app()->getLocale();

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'headline' => $this->getContent($article, 'title', $locale),
            'description' => $this->getContent($article, 'excerpt', $locale) ?: $this->generateExcerpt(
                $this->getContent($article, 'content', $locale),
                160
            ),
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $article->getUrl(),
            ],
        ];

        // Add image if available
        if ($featuredImage = $this->getFeaturedImageUrl($article)) {
            $jsonLd['image'] = $featuredImage;
        }

        // Add author information
        if ($article->author) {
            $authorData = [
                '@type' => 'Person',
                'name' => $article->author->name,
            ];

            if (\Illuminate\Support\Facades\Route::has('profile.show')) {
                $authorData['url'] = route('profile.show', $article->author->id);
            }

            $jsonLd['author'] = $authorData;
        }

        // Add publisher information
        $jsonLd['publisher'] = [
            '@type' => 'Organization',
            'name' => self::SEO_SETTINGS['organization_name'],
            'url' => self::SEO_SETTINGS['organization_url'],
            'logo' => [
                '@type' => 'ImageObject',
                'url' => self::SEO_SETTINGS['organization_logo'],
            ],
        ];

        return $jsonLd;
    }

    /**
     * Generate OpenGraph meta tags
     *
     * @param  string  $locale  Language code
     * @return array<string, mixed> OpenGraph tags
     *
     * @throws SeoGenerationException
     */
    public function generateOpenGraphTags(Article $article, string $locale = 'ar'): array
    {
        try {
            $this->validateLocale($locale);

            $tags = [
                'og:type' => 'article',
                'og:title' => $this->truncateText(
                    $this->getContent($article, 'seo_title', $locale) ?: $this->getContent($article, 'title', $locale),
                    self::SEO_SETTINGS['title_optimal_length']
                ),
                'og:description' => $this->truncateText(
                    $this->getContent($article, 'seo_description', $locale) ?: $this->generateExcerpt(
                        $this->getContent($article, 'excerpt', $locale) ?: $this->getContent($article, 'content', $locale),
                        self::SEO_SETTINGS['description_optimal_length']
                    ),
                    self::SEO_SETTINGS['description_optimal_length']
                ),
                'og:url' => $article->getUrl(),
                'og:locale' => $this->getLocaleCode($locale),
                'og:site_name' => self::SEO_SETTINGS['organization_name'],
            ];

            // Add featured image
            if ($featuredImage = $this->getFeaturedImageUrl($article)) {
                $tags['og:image'] = $featuredImage;
                $tags['og:image:width'] = (string) self::SEO_SETTINGS['og_image_width'];
                $tags['og:image:height'] = (string) self::SEO_SETTINGS['og_image_height'];
                $tags['og:image:alt'] = $this->getContent($article, 'title', $locale);
            }

            // Article-specific tags
            if ($article->published_at) {
                $tags['article:published_time'] = $article->published_at->toIso8601String();
            }
            $tags['article:modified_time'] = $article->updated_at->toIso8601String();

            if ($article->author) {
                $tags['article:author'] = $article->author->name;
            }

            if ($article->category) {
                $tags['article:section'] = $this->getContent($article->category, 'name', $locale);
            }

            // Add tags
            if ($article->tags->isNotEmpty()) {
                $tags['article:tag'] = $article->tags->pluck('name')->toArray();
            }

            return $tags;
        } catch (\Exception $e) {
            throw SeoGenerationException::openGraphFailed($e->getMessage());
        }
    }

    /**
     * Generate Twitter Card meta tags
     *
     * @param  string  $locale  Language code
     * @return array<string, mixed> Twitter Card tags
     *
     * @throws SeoGenerationException
     */
    public function generateTwitterCardTags(Article $article, string $locale = 'ar'): array
    {
        try {
            $this->validateLocale($locale);

            $tags = [
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $this->truncateText(
                    $this->getContent($article, 'seo_title', $locale) ?: $this->getContent($article, 'title', $locale),
                    self::SEO_SETTINGS['title_optimal_length']
                ),
                'twitter:description' => $this->truncateText(
                    $this->getContent($article, 'seo_description', $locale) ?: $this->generateExcerpt(
                        $this->getContent($article, 'excerpt', $locale) ?: $this->getContent($article, 'content', $locale),
                        self::SEO_SETTINGS['description_optimal_length']
                    ),
                    self::SEO_SETTINGS['description_optimal_length']
                ),
            ];

            // Add featured image
            if ($featuredImage = $this->getFeaturedImageUrl($article)) {
                $tags['twitter:image'] = $featuredImage;
                $tags['twitter:image:alt'] = $this->getContent($article, 'title', $locale);
            }

            // Add site and creator handles if configured
            if ($siteHandle = config('services.twitter.site')) {
                $tags['twitter:site'] = $siteHandle;
            }

            if ($creatorHandle = config('services.twitter.creator')) {
                $tags['twitter:creator'] = $creatorHandle;
            }

            return $tags;
        } catch (\Exception $e) {
            throw SeoGenerationException::twitterCardFailed($e->getMessage());
        }
    }

    /**
     * Generate standard meta tags
     *
     * @param  string  $locale  Language code
     * @return array<string, string> Meta tags
     */
    public function generateMetaTags(Article $article, string $locale = 'ar'): array
    {
        $tags = [
            'robots' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
            'viewport' => 'width=device-width, initial-scale=1',
            'language' => $locale,
        ];

        if ($article->author) {
            $tags['author'] = $article->author->name;
            $tags['creator'] = $article->author->name;
        }

        $tags['publisher'] = self::SEO_SETTINGS['organization_name'];
        $tags['copyright'] = self::SEO_SETTINGS['organization_name'].' © '.now()->year;
        $tags['revisit-after'] = '7 days';

        // Add reading time
        if ($article->reading_time_minutes > 0) {
            $tags['article:reading_time'] = $article->reading_time_minutes.' minutes';
        }

        return $tags;
    }

    /**
     * Validate SEO data for article
     *
     * @param  string  $locale  Language code
     */
    public function validateSeoData(Article $article, string $locale = 'ar'): SeoValidationResult
    {
        $result = new SeoValidationResult(
            isValid: true,
            errors: [],
            warnings: [],
            successes: []
        );

        // Validate title
        $title = $this->getContent($article, 'seo_title', $locale) ?: $this->getContent($article, 'title', $locale);
        $titleLength = mb_strlen($title);

        if ($titleLength < self::SEO_SETTINGS['title_min_length']) {
            $result->addError("Title is too short ({$titleLength} chars). Minimum: ".self::SEO_SETTINGS['title_min_length']);
        } elseif ($titleLength > self::SEO_SETTINGS['title_max_length']) {
            $result->addWarning("Title is too long ({$titleLength} chars). Maximum: ".self::SEO_SETTINGS['title_max_length']);
        } else {
            $result->addSuccess('Title length is optimal');
        }

        // Validate description
        $description = $this->getContent($article, 'seo_description', $locale) ?: $this->getContent($article, 'excerpt', $locale);
        $descriptionLength = mb_strlen($description);

        if (empty($description)) {
            $result->addError('SEO description is missing');
        } elseif ($descriptionLength < self::SEO_SETTINGS['description_min_length']) {
            $result->addError("Description is too short ({$descriptionLength} chars). Minimum: ".self::SEO_SETTINGS['description_min_length']);
        } elseif ($descriptionLength > self::SEO_SETTINGS['description_max_length']) {
            $result->addWarning("Description is too long ({$descriptionLength} chars). Maximum: ".self::SEO_SETTINGS['description_max_length']);
        } else {
            $result->addSuccess('Description length is optimal');
        }

        // Validate keywords
        if (empty($article->seo_keywords)) {
            $result->addWarning('SEO keywords are missing');
        } else {
            $keywords = array_filter(array_map('trim', explode(',', $article->seo_keywords)));
            $keywordCount = count($keywords);

            if ($keywordCount < self::SEO_SETTINGS['keywords_min_count']) {
                $result->addWarning("Too few keywords ({$keywordCount}). Recommended: ".self::SEO_SETTINGS['keywords_min_count'].'-'.self::SEO_SETTINGS['keywords_max_count']);
            } elseif ($keywordCount > self::SEO_SETTINGS['keywords_max_count']) {
                $result->addWarning("Too many keywords ({$keywordCount}). Recommended: ".self::SEO_SETTINGS['keywords_min_count'].'-'.self::SEO_SETTINGS['keywords_max_count']);
            } else {
                $result->addSuccess('Keyword count is optimal');
            }
        }

        // Validate content length
        $content = $this->getContent($article, 'content', $locale);
        $wordCount = str_word_count(strip_tags($content));

        if ($wordCount < self::SEO_SETTINGS['min_content_length']) {
            $result->addWarning("Content is too short ({$wordCount} words). Minimum recommended: ".self::SEO_SETTINGS['min_content_length']);
        } else {
            $result->addSuccess('Content length is sufficient');
        }

        // Validate featured image
        if (empty($article->featured_image) && empty($article->og_image)) {
            $result->addWarning('Featured image is missing');
        } else {
            $result->addSuccess('Featured image is present');
        }

        // Validate slug
        if (empty($article->slug)) {
            $result->addError('Article slug is missing');
        } else {
            $result->addSuccess('Article has URL-friendly slug');
        }

        // Set metrics
        $result->setMetric('title_length', $titleLength);
        $result->setMetric('description_length', $descriptionLength);
        $result->setMetric('keyword_count', isset($keywordCount) ? $keywordCount : 0);
        $result->setMetric('word_count', $wordCount);

        // Calculate final score
        $result->updateScore();

        return $result;
    }

    /**
     * Generate SEO improvement suggestions
     *
     * @param  string  $locale  Language code
     * @return array<string> Suggestions
     */
    public function generateSuggestions(Article $article, string $locale = 'ar'): array
    {
        $suggestions = [];
        $validation = $this->validateSeoData($article, $locale);

        // Add all errors and warnings as suggestions
        $suggestions = array_merge($validation->errors, $validation->warnings);

        // Additional suggestions
        if (empty($article->category_id)) {
            $suggestions[] = 'Consider assigning a category to improve content organization';
        }

        if ($article->tags->isEmpty()) {
            $suggestions[] = 'Add tags to improve discoverability';
        }

        if (empty($article->series_id)) {
            $suggestions[] = 'Consider linking this article to a series for better content structure';
        }

        if (! $article->featured) {
            $suggestions[] = 'Consider featuring this article if it\'s high-quality content';
        }

        // Check for internal links
        $content = $this->getContent($article, 'content', $locale);
        if (substr_count($content, '<a href') < 3) {
            $suggestions[] = 'Add more internal links to improve SEO and user engagement';
        }

        // Check for headings
        if (substr_count($content, '<h2') < 2) {
            $suggestions[] = 'Use more heading tags (H2, H3) to improve content structure';
        }

        return $suggestions;
    }

    /**
     * Generate XML sitemap entry for article
     *
     * @return array<string, mixed> Sitemap entry data
     *
     * @throws SeoGenerationException
     */
    public function generateSitemapEntry(Article $article): array
    {
        try {
            if (! $article->isPublished()) {
                throw SeoGenerationException::sitemapFailed('Article is not published');
            }

            return [
                'loc' => $article->getUrl(),
                'lastmod' => $article->updated_at->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => $article->featured ? '0.9' : '0.7',
                'images' => array_filter([
                    $this->getFeaturedImageUrl($article),
                ]),
            ];
        } catch (\Exception $e) {
            throw SeoGenerationException::sitemapFailed($e->getMessage());
        }
    }

    /**
     * Get content in specific locale from translatable field
     *
     * @param  Article|object  $model  Model with translatable fields
     * @param  string  $field  Field name
     * @param  string  $locale  Locale code
     */
    public function getContent(object $model, string $field, string $locale): string
    {
        try {
            if (method_exists($model, 'getTranslation')) {
                return $model->getTranslation($field, $locale, false) ?? '';
            }

            // Fallback for non-translatable fields
            return $model->{$field} ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Generate excerpt from content
     *
     * @param  string  $content  Full content
     * @param  int  $length  Maximum length in characters
     * @return string Excerpt
     */
    public function generateExcerpt(string $content, int $length = 160): string
    {
        // Strip HTML tags
        $text = strip_tags($content);

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $this->truncateText($text, $length);
    }

    /**
     * Truncate text to maximum length
     *
     * @param  string  $text  Text to truncate
     * @param  int  $maxLength  Maximum length
     * @param  string  $suffix  Suffix to add if truncated
     * @return string Truncated text
     */
    public function truncateText(string $text, int $maxLength, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $maxLength - mb_strlen($suffix));

        // Try to truncate at word boundary
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > ($maxLength * 0.8)) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return $truncated.$suffix;
    }

    /**
     * Sanitize text for HTML attributes
     *
     * @param  string  $text  Text to sanitize
     * @return string Sanitized text
     */
    public function sanitizeText(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get OpenGraph locale code from language code
     *
     * @param  string  $locale  Language code (ar, en, etc.)
     * @return string OpenGraph locale code (ar_SA, en_US, etc.)
     */
    public function getLocaleCode(string $locale): string
    {
        return self::LOCALE_CODES[$locale] ?? 'en_US';
    }

    /**
     * Get featured image URL
     *
     * @return string|null Full URL to featured image
     */
    public function getFeaturedImageUrl(Article $article): ?string
    {
        $image = $article->getOgImage();

        if (empty($image)) {
            return null;
        }

        // If already a full URL, return as-is
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        // Generate full URL from storage path
        return Storage::disk('public')->url($image);
    }

    /**
     * Validate locale
     *
     * @throws SeoException
     */
    private function validateLocale(string $locale): void
    {
        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            throw SeoException::invalidLocale($locale, self::SUPPORTED_LOCALES);
        }
    }

    /**
     * Get SEO settings
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return self::SEO_SETTINGS;
    }

    /**
     * Get supported locales
     *
     * @return array<string>
     */
    public function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }
}
