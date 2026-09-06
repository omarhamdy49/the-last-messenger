<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Shared\Services\RichTextSanitizer;
use Tests\TestCase;

/**
 * The one HTML allowlist every rich-text column passes through.
 *
 * Articles and media were the two highest-volume rich-text paths and had NO sanitisation on
 * input and none on output — `content` and `summary` render with {!! !!} in four places
 * including the admin panel, which is same-origin with user management. Since the `editor`
 * role holds create + publish articles, that was an editor -> super_admin escalation.
 * YouTube titles/descriptions/transcripts reach the same columns from a third party.
 */
class RichTextSanitizerTest extends TestCase
{
    private function sanitizer(): RichTextSanitizer
    {
        return app(RichTextSanitizer::class);
    }

    /**
     * @return array<string, string>
     */
    public static function payloads(): array
    {
        return [
            'script tag' => '<p>hi</p><script>alert(document.cookie)</script>',
            'img onerror' => '<img src=x onerror="fetch(1)">',
            'javascript href' => '<a href="javascript:alert(1)">x</a>',
            'svg onload' => '<svg onload=alert(1)></svg>',
            'iframe' => '<iframe src="//evil.test"></iframe>',
            'style url javascript' => '<div style="background:url(javascript:alert(1))">x</div>',
            'event handler on allowed tag' => '<li data-list="bullet" onclick="alert(1)">x</li>',
            'data uri image' => '<img src="data:text/html;base64,PHNjcmlwdD4=">',
        ];
    }

    public function test_every_payload_is_neutralised(): void
    {
        $leaks = [];

        foreach (self::payloads() as $name => $payload) {
            $out = (string) $this->sanitizer()->clean($payload);

            if (preg_match('/<script|<iframe|<svg|on[a-z]+\s*=|javascript:|data:text\/html/i', $out)) {
                $leaks[] = "{$name} -> {$out}";
            }
        }

        $this->assertSame([], $leaks, "sanitizer let a payload through:\n  ".implode("\n  ", $leaks));
    }

    /**
     * Sanitisation that eats legitimate content gets switched off by the next person who
     * hits it, so the allowlist has to be pinned from both directions.
     */
    public function test_legitimate_editor_markup_survives(): void
    {
        $in = '<p><strong>bold</strong> <em>italic</em></p>'
            .'<blockquote>quote</blockquote>'
            .'<h2>heading</h2>'
            .'<a href="https://example.com" rel="noopener">link</a>'
            .'<ul><li data-list="bullet">one</li></ul>'
            .'<ol><li data-list="ordered">two</li></ol>';

        $out = (string) $this->sanitizer()->clean($in);

        foreach (['<strong>', '<em>', '<blockquote>', '<h2>', 'https://example.com', '<ul>', '<ol>'] as $keep) {
            $this->assertStringContainsString($keep, $out, "sanitizer stripped legitimate markup: {$keep}");
        }
    }

    /**
     * Quill 2 encodes bullet-vs-ordered on the <li> itself. addAttribute() alone does not
     * preserve it — HTML.Allowed builds its own attribute whitelist and filters out anything
     * absent from it, so `li` had to become `li[data-list]`. Without this the attribute was
     * silently stripped on every save and every list round-tripped as plain <li>.
     */
    public function test_quill_list_markers_round_trip(): void
    {
        $out = (string) $this->sanitizer()->clean('<ul><li data-list="bullet">x</li></ul>');

        $this->assertStringContainsString('data-list="bullet"', $out);
    }

    /**
     * The cross-module embed cards (verse / hadith / seerah) must survive, or the citation
     * link back to the source is lost on every save.
     */
    public function test_module_embeds_survive(): void
    {
        $out = (string) $this->sanitizer()->clean(
            '<div class="module-embed" data-module="verse" data-id="262">Al-Baqarah 255</div>'
        );

        $this->assertStringContainsString('data-module="verse"', $out);
        $this->assertStringContainsString('data-id="262"', $out);
    }

    /**
     * The WHOLE payload, not just module + id.
     *
     * The editor rebuilds a card from these attributes alone and discards the rendered
     * children, so an attribute stripped here is content the next save writes back as an
     * empty card. Only data-module and data-id were allowed, which is how ten verse cards
     * were blanked by an editor who did nothing but open an event and press save.
     */
    public function test_module_embed_payload_attributes_survive(): void
    {
        $out = (string) $this->sanitizer()->clean(
            '<div class="module-embed module-embed--verse" data-module="verse" data-id="262"'
            .' data-label="سورة البقرة" data-text="اللَّهُ لَا إِلَٰهَ إِلَّا هُوَ"'
            .' data-href="https://example.test/quran/2" data-link-label="البقرة ٢٥٥"'
            .' data-reference="البقرة: ٢٥٥" data-dir="rtl" dir="rtl">'
            .'<span class="module-embed__label">سورة البقرة</span></div>'
        );

        foreach ([
            'data-module="verse"',
            'data-id="262"',
            'data-label="سورة البقرة"',
            'data-text="اللَّهُ لَا إِلَٰهَ إِلَّا هُوَ"',
            'data-href="https://example.test/quran/2"',
            'data-link-label="البقرة ٢٥٥"',
            'data-reference="البقرة: ٢٥٥"',
            'data-dir="rtl"',
        ] as $attribute) {
            $this->assertStringContainsString($attribute, $out, "embed payload lost {$attribute}");
        }
    }

    /**
     * data-href is written straight into <a href> when the card is rebuilt, so it is a link
     * and gets a link's scheme restrictions — allowing the attribute must not open a hole.
     */
    public function test_module_embed_href_is_scheme_restricted(): void
    {
        $out = (string) $this->sanitizer()->clean(
            '<div class="module-embed" data-module="verse" data-id="1"'
            .' data-href="javascript:alert(document.cookie)">x</div>'
        );

        $this->assertStringNotContainsString('javascript:', $out);
    }

    public function test_plain_text_passes_through_unharmed(): void
    {
        // YouTube descriptions are plain text, and they land in the same columns.
        $text = 'Watch the full series at https://example.com - episode 3 of 10!';

        $this->assertSame($text, $this->sanitizer()->clean($text));
    }

    public function test_empty_input_becomes_null(): void
    {
        $this->assertNull($this->sanitizer()->clean(null));
        $this->assertNull($this->sanitizer()->clean('   '));
        $this->assertNull($this->sanitizer()->clean('<script>alert(1)</script>'));
    }
}
