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
 *   - Embeds: div[class|dir] plus the full moduleEmbed payload (data-module, data-id,
 *             data-label, data-text, data-href, data-link-label, data-reference, data-dir)
 *             + span/cite class, so the moduleEmbed cards (verse / hadith / seerah) survive
 *             round-trips. The payload is the card — see the note in purifier().
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
            .'div[class|data-module|data-id|data-label|data-text|data-href|data-link-label'
            .'|data-reference|data-dir|dir],span[class],cite[class]'
        );
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.DefinitionID', 'admin-content-rich-text');
        $config->set('HTML.DefinitionRev', 3);

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
            // moduleEmbed blot as <div class="module-embed"> carrying its whole payload in
            // data-* attributes. Every one of them has to survive here — not just
            // module/id. The editor rebuilds a card purely from these
            // attributes (ModuleEmbed.value() in resources/js/admin/rich-text.js) and throws
            // the rendered children away, so every attribute stripped here is content the
            // next save writes back as an empty card. Keeping only data-module + data-id is
            // what blanked ten verse cards: the label, the text and the citation link all
            // died on the way in, and the editor faithfully persisted the husk.
            $definition->addAttribute('div', 'data-module', 'Enum#verse,hadith,sahabi,seerah');
            $definition->addAttribute('div', 'data-id', 'Text');
            $definition->addAttribute('div', 'data-label', 'Text');
            $definition->addAttribute('div', 'data-text', 'Text');
            // 'URI', not 'Text': this is written straight into <a href> when the card is
            // rebuilt, so it goes through URI.AllowedSchemes like any other link.
            $definition->addAttribute('div', 'data-href', 'URI');
            $definition->addAttribute('div', 'data-link-label', 'Text');
            $definition->addAttribute('div', 'data-reference', 'Text');
            $definition->addAttribute('div', 'data-dir', 'Enum#rtl,ltr');
        }

        self::$purifier = new HTMLPurifier($config);

        return self::$purifier;
    }
}
