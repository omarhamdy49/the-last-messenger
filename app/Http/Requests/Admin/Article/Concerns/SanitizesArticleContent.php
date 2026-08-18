<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Article\Concerns;

use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;

/**
 * Purifies article rich text before it is persisted.
 *
 * Articles were the one high-volume rich-text path with NO sanitisation on input and none
 * on output. `content` and `summary` are rendered with {!! !!} in four places — the public
 * reader (show.blade.php:787, :974), the admin show page, the admin preview page and the
 * preview modal — and the request rules validated the body as a bare `required` array.
 *
 * That is an editor -> super_admin escalation, not just a visitor-facing XSS: the `editor`
 * role holds `create articles` + `publish articles` (RolePermissionSeeder), and the admin
 * panel is same-origin with user management. Quill's client-side filtering is irrelevant —
 * a crafted POST never runs it.
 *
 * The shape is why this needs its own concern rather than SanitizesRichText::
 * sanitizeRichTextField(). `content[$locale]` is EITHER a legacy HTML string OR an array of
 * blocks (see ArticleLocale::renderBlocksToHtml). Only `wysiwyg` blocks carry author HTML;
 * verse/hadith/sahabi/seerah blocks are re-rendered server-side through e(), so they hold
 * ids and labels rather than markup and must be left untouched — purifying them would
 * strip the embed structure the reader depends on.
 */
trait SanitizesArticleContent
{
    use SanitizesRichText;

    /**
     * Purify `content` (both shapes) and `summary` in place, before validation.
     */
    protected function sanitizeArticleRichText(): void
    {
        $payload = $this->all();
        $changed = false;

        $content = $payload['content'] ?? null;

        if (is_array($content)) {
            foreach ($content as $locale => $value) {
                $content[$locale] = $this->sanitizeLocaleContent($value);
            }

            $payload['content'] = $content;
            $changed = true;
        }

        $summary = $payload['summary'] ?? null;

        if (is_array($summary)) {
            foreach ($summary as $locale => $value) {
                $summary[$locale] = $this->sanitizeRichTextValue(is_string($value) ? $value : null);
            }

            $payload['summary'] = $summary;
            $changed = true;
        }

        if ($changed) {
            $this->replace($payload);
        }
    }

    /**
     * One locale's content: legacy HTML string, or a block array.
     */
    private function sanitizeLocaleContent(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->sanitizeRichTextValue($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        // `content[$locale]` may itself be `['body' => …]` (the persisted shape) or a bare
        // list of blocks (what the form posts). Handle both without assuming.
        if (array_key_exists('body', $value)) {
            $value['body'] = $this->sanitizeLocaleContent($value['body']);

            return $value;
        }

        foreach ($value as $i => $block) {
            if (! is_array($block)) {
                continue;
            }

            // Only wysiwyg blocks hold author-supplied markup.
            if (($block['type'] ?? '') === 'wysiwyg' && isset($block['content']) && is_string($block['content'])) {
                $value[$i]['content'] = (string) $this->sanitizeRichTextValue($block['content']);
            }
        }

        return $value;
    }
}
