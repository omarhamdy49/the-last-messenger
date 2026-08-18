<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * The single HTML allowlist for everything this application persists as rich text.
 *
 * Extracted from App\Http\Requests\Admin\Content\Concerns\SanitizesRichText so the domain
 * layer can reach it too. YouTube titles/descriptions/transcripts arrive from a third party
 * and are rendered with {!! !!}, so they need the same treatment as admin WYSIWYG input —
 * but a Domain service must not import from App\Http\Requests (deptrac enforces that, and
 * it is the correct boundary). One sanitiser, used from both sides, keeps the invariant
 * simple: nothing reaches a rich-text column without passing through here.
 *
 * Allowlist (everything else is stripped, including all scripts, styles, event handlers,
 * iframes and images):
 *   - Block:  p, br, blockquote, h2, h3, h4
 *   - Inline: strong, b, em, i, u, s
 *   - Lists:  ol, ul, li (+ data-list="ordered|bullet" — Quill 2 encodes the list style on
 *             <li>, so it must survive sanitisation to round-trip)
 *   - Links:  a[href] restricted to http / https / mailto
 *   - Embeds: div[class|data-module|data-id|dir] + span/cite class, so the moduleEmbed
 *             cards (verse / hadith / seerah) survive round-trips
 */
final class RichTextSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    /**
     * Purify a single value. Empty input (and input that purifies to nothing) becomes null.
     */
    public function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $purified = trim($this->purifier()->purify($html));

        return $purified === '' ? null : $purified;
    }

    /**
     * Purify a locale-keyed map, preserving keys and non-string values.
     *
     * @param  array<string, mixed>  $map
     * @return array<string, string|null>
     */
    public function cleanMap(array $map): array
    {
        $clean = [];

        foreach ($map as $locale => $value) {
            $clean[$locale] = $this->clean(is_string($value) ? $value : null);
        }

        return $clean;
    }

    private function purifier(): HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        // No filesystem cache: definitions are tiny and admin writes are rare.
        $config->set('Cache.DefinitionImpl', null);
        $config->set(
            'HTML.Allowed',
            'p,br,blockquote,h2,h3,h4,strong,b,em,i,u,s,a[href|target|rel],ol,ul,li[data-list],'
            .'div[class|data-module|data-id|dir],span[class],cite[class]'
        );
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.DefinitionID', 'admin-content-rich-text');
        $config->set('HTML.DefinitionRev', 2);

        $definition = $config->maybeGetRawHTMLDefinition();

        if ($definition !== null) {
            // Quill 2 marks bullet vs ordered items via data-list on <li>.
            //
            // addAttribute() alone is NOT enough: HTML.Allowed is a shorthand that builds its
            // own element/attribute whitelist, and anything absent from it is filtered out
            // regardless of the raw definition. `li` was listed bare above, so data-list was
            // silently stripped on every save and Quill lists round-tripped as plain <li> —
            // despite this comment claiming otherwise. It is `li[data-list]` in HTML.Allowed
            // that actually preserves it. (The embed <div> survived only because its
            // attributes were spelled out in HTML.Allowed.)
            $definition->addAttribute('li', 'data-list', 'Enum#ordered,bullet');

            // Cross-module embeds (Quran verse / hadith / seerah event) are emitted by the
            // moduleEmbed blot as <div class="module-embed" data-module data-id>. Without
            // these surviving purification the card is stripped back to bare text and the
            // citation link to the source is lost.
            $definition->addAttribute('div', 'data-module', 'Enum#verse,hadith,sahabi,seerah');
            $definition->addAttribute('div', 'data-id', 'Text');
        }

        self::$purifier = new HTMLPurifier($config);

        return self::$purifier;
    }
}
