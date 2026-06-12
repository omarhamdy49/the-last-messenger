<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use Illuminate\Support\Str;

/**
 * Table of Contents Generator Service
 *
 * Extracts H2 and H3 headings from HTML content and returns a structured
 * array suitable for rendering a table of contents.
 * Extracted from ArticleController to keep parsing logic in the domain layer.
 */
class TocGeneratorService
{
    /**
     * Generate a table of contents from HTML content or content blocks.
     *
     * Scans for <h2> and <h3> tags. Each entry carries the heading text,
     * a URL-safe anchor id derived from that text, and the heading level.
     *
     * @param  string|array  $content  Raw HTML article body or blocks array.
     * @return array<int, array{title: string, link: string, level: int}>
     */
    public function generate(string|array $content): array
    {
        $html = is_array($content) ? $this->extractHtmlFromBlocks($content) : $content;

        $toc = [];

        preg_match_all('/<h([2-3])[^>]*>(.*?)<\/h[2-3]>/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $level = (int) $match[1];
            $text = strip_tags($match[2]);
            $id = Str::slug($text);

            $toc[] = [
                'title' => $text,
                'link' => $id,
                'level' => $level,
            ];
        }

        return $toc;
    }

    /**
     * Extract HTML content from WYSIWYG blocks for TOC generation.
     */
    private function extractHtmlFromBlocks(array $blocks): string
    {
        $html = '';
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'wysiwyg') {
                $html .= ($block['content'] ?? '');
            }
        }

        return $html;
    }
}
