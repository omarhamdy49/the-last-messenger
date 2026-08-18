<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content\Concerns;

use App\Domain\Shared\Services\RichTextSanitizer;

/**
 * Server-side sanitisation for WYSIWYG (Quill) HTML before persistence.
 *
 * The allowlist itself now lives in App\Domain\Shared\Services\RichTextSanitizer, because
 * the domain layer needs it too — YouTube titles/descriptions/transcripts come from a third
 * party and are rendered with {!! !!}, and a Domain service must not import from
 * App\Http\Requests. This trait is the FormRequest-shaped adapter over that sanitiser.
 */
trait SanitizesRichText
{
    /**
     * Sanitize one locale-keyed rich-text input map in place
     * (e.g. body[ar], body[en], ...) before validation runs.
     */
    protected function sanitizeRichTextField(string $field): void
    {
        $map = $this->input($field);

        if (! is_array($map)) {
            return;
        }

        // data_set handles nested fields like "settings.footer_about".
        $payload = $this->all();
        data_set($payload, $field, $this->richTextSanitizer()->cleanMap($map));
        $this->replace($payload);
    }

    /**
     * Purify a single rich-text value. Empty input stays null.
     */
    protected function sanitizeRichTextValue(?string $html): ?string
    {
        return $this->richTextSanitizer()->clean($html);
    }

    private function richTextSanitizer(): RichTextSanitizer
    {
        return app(RichTextSanitizer::class);
    }
}
