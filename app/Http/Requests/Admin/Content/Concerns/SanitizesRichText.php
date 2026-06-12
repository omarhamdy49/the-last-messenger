<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content\Concerns;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Server-side sanitization for WYSIWYG (Quill) HTML before persistence.
 *
 * Allowlist (everything else is stripped, including all scripts, styles,
 * event handlers, iframes and images):
 *   - Block:  p, br, blockquote, h2, h3, h4
 *   - Inline: strong, b, em, i, u, s
 *   - Lists:  ol, ul, li (+ data-list="ordered|bullet" — Quill 2 encodes the
 *             list style on <li>, so it must survive sanitization to round-trip)
 *   - Links:  a[href] restricted to http / https / mailto schemes
 */
trait SanitizesRichText
{
    private static ?HTMLPurifier $richTextPurifier = null;

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

        $clean = [];

        foreach ($map as $locale => $html) {
            $clean[$locale] = $this->sanitizeRichTextValue(is_string($html) ? $html : null);
        }

        // data_set handles nested fields like "settings.footer_about".
        $payload = $this->all();
        data_set($payload, $field, $clean);
        $this->replace($payload);
    }

    /**
     * Purify a single rich-text value. Empty input stays null.
     */
    protected function sanitizeRichTextValue(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $purified = trim($this->richTextPurifier()->purify($html));

        return $purified === '' ? null : $purified;
    }

    /**
     * Lazily build the shared purifier with the allowlist documented above.
     */
    private function richTextPurifier(): HTMLPurifier
    {
        if (self::$richTextPurifier !== null) {
            return self::$richTextPurifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        // No filesystem cache: definitions are tiny and admin writes are rare.
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p,br,blockquote,h2,h3,h4,strong,b,em,i,u,s,a[href],ol,ul,li');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.DefinitionID', 'admin-content-rich-text');
        $config->set('HTML.DefinitionRev', 1);

        $definition = $config->maybeGetRawHTMLDefinition();

        if ($definition !== null) {
            // Quill 2 marks bullet vs ordered items via data-list on <li>.
            $definition->addAttribute('li', 'data-list', 'Enum#ordered,bullet');
        }

        self::$richTextPurifier = new HTMLPurifier($config);

        return self::$richTextPurifier;
    }
}
