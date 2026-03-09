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
     * Generate a table of contents from HTML content.
     *
     * Scans for <h2> and <h3> tags. Each entry carries the heading text,
     * a URL-safe anchor id derived from that text, and the heading level.
     *
     * @param  string  $content  Raw HTML article body.
     * @return array<int, array{id: string, text: string, level: int}>
     */
    public function generate(string $content): array
    {
        $toc = [];

        preg_match_all('/<h([2-3])[^>]*>(.*?)<\/h[2-3]>/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $level = (int) $match[1];
            $text  = strip_tags($match[2]);
            $id    = Str::slug($text);

            $toc[] = [
                'id'    => $id,
                'text'  => $text,
                'level' => $level,
            ];
        }

        return $toc;
    }
}
