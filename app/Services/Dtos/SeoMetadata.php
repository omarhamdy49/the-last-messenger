<?php

declare(strict_types=1);

namespace App\Services\Dtos;

/**
 * SEO Metadata Data Transfer Object
 *
 * Encapsulates all SEO-related metadata for an article or content piece.
 * Provides rendering capabilities for meta tags and JSON-LD structured data.
 */
class SeoMetadata
{
    /**
     * Create new SEO Metadata instance
     *
     * @param  string  $title  Page title
     * @param  string  $description  Page description
     * @param  string  $keywords  Comma-separated keywords
     * @param  string  $canonicalUrl  Canonical URL
     * @param  string|null  $featuredImage  Featured image URL
     * @param  array<string, mixed>  $openGraph  OpenGraph tags
     * @param  array<string, mixed>  $twitterCard  Twitter Card tags
     * @param  array<string, mixed>  $jsonLd  JSON-LD structured data
     * @param  array<string, mixed>  $metaTags  Additional meta tags
     * @param  string  $locale  Current locale
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $keywords,
        public string $canonicalUrl,
        public ?string $featuredImage = null,
        public array $openGraph = [],
        public array $twitterCard = [],
        public array $jsonLd = [],
        public array $metaTags = [],
        public string $locale = 'ar'
    ) {}

    /**
     * Render all meta tags as HTML string
     *
     * @return string HTML meta tags
     */
    public function renderMetaTags(): string
    {
        $html = [];

        // Standard meta tags
        $html[] = sprintf('<meta name="description" content="%s">', $this->escapeAttribute($this->description));
        $html[] = sprintf('<meta name="keywords" content="%s">', $this->escapeAttribute($this->keywords));
        $html[] = sprintf('<link rel="canonical" href="%s">', $this->escapeAttribute($this->canonicalUrl));

        // Additional meta tags
        foreach ($this->metaTags as $name => $content) {
            if (str_starts_with($name, 'property:')) {
                $property = str_replace('property:', '', $name);
                $html[] = sprintf('<meta property="%s" content="%s">', $this->escapeAttribute($property), $this->escapeAttribute((string) $content));
            } else {
                $html[] = sprintf('<meta name="%s" content="%s">', $this->escapeAttribute($name), $this->escapeAttribute((string) $content));
            }
        }

        // OpenGraph tags
        foreach ($this->openGraph as $property => $content) {
            if (is_array($content)) {
                foreach ($content as $value) {
                    $html[] = sprintf('<meta property="%s" content="%s">', $this->escapeAttribute($property), $this->escapeAttribute((string) $value));
                }
            } else {
                $html[] = sprintf('<meta property="%s" content="%s">', $this->escapeAttribute($property), $this->escapeAttribute((string) $content));
            }
        }

        // Twitter Card tags
        foreach ($this->twitterCard as $name => $content) {
            $html[] = sprintf('<meta name="%s" content="%s">', $this->escapeAttribute($name), $this->escapeAttribute((string) $content));
        }

        return implode("\n    ", $html);
    }

    /**
     * Render JSON-LD structured data as script tag
     *
     * @return string HTML script tag with JSON-LD
     */
    public function renderJsonLd(): string
    {
        if (empty($this->jsonLd)) {
            return '';
        }

        $jsonData = json_encode($this->jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return sprintf('<script type="application/ld+json">%s</script>', $jsonData);
    }

    /**
     * Render all SEO elements (meta tags + JSON-LD)
     *
     * @return string Complete SEO HTML
     */
    public function render(): string
    {
        $output = [];

        $output[] = $this->renderMetaTags();

        $jsonLd = $this->renderJsonLd();
        if (! empty($jsonLd)) {
            $output[] = $jsonLd;
        }

        return implode("\n    ", $output);
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'canonical_url' => $this->canonicalUrl,
            'featured_image' => $this->featuredImage,
            'open_graph' => $this->openGraph,
            'twitter_card' => $this->twitterCard,
            'json_ld' => $this->jsonLd,
            'meta_tags' => $this->metaTags,
            'locale' => $this->locale,
        ];
    }

    /**
     * Get OpenGraph tag value
     *
     * @param  string  $property  Property name (with or without og: prefix)
     * @return mixed|null
     */
    public function getOpenGraphTag(string $property): mixed
    {
        $property = str_starts_with($property, 'og:') ? $property : 'og:'.$property;

        return $this->openGraph[$property] ?? null;
    }

    /**
     * Get Twitter Card tag value
     *
     * @param  string  $name  Tag name (with or without twitter: prefix)
     * @return mixed|null
     */
    public function getTwitterCardTag(string $name): mixed
    {
        $name = str_starts_with($name, 'twitter:') ? $name : 'twitter:'.$name;

        return $this->twitterCard[$name] ?? null;
    }

    /**
     * Escape HTML attribute value
     */
    private function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Check if SEO metadata is complete
     */
    public function isComplete(): bool
    {
        return ! empty($this->title)
            && ! empty($this->description)
            && ! empty($this->keywords)
            && ! empty($this->canonicalUrl)
            && ! empty($this->openGraph)
            && ! empty($this->twitterCard);
    }

    /**
     * Get missing required fields
     *
     * @return array<string>
     */
    public function getMissingFields(): array
    {
        $missing = [];

        if (empty($this->title)) {
            $missing[] = 'title';
        }
        if (empty($this->description)) {
            $missing[] = 'description';
        }
        if (empty($this->keywords)) {
            $missing[] = 'keywords';
        }
        if (empty($this->canonicalUrl)) {
            $missing[] = 'canonical_url';
        }
        if (empty($this->openGraph)) {
            $missing[] = 'open_graph';
        }
        if (empty($this->twitterCard)) {
            $missing[] = 'twitter_card';
        }

        return $missing;
    }
}
