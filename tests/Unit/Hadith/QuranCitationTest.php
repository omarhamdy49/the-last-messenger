<?php

declare(strict_types=1);

namespace Tests\Unit\Hadith;

use App\Domain\Hadith\Support\QuranCitation;
use Tests\TestCase;

/**
 * Covers every shape the marker takes in the imported corpus, plus the escaping
 * contract — the matn is third-party text rendered with {!! !!}, so anything
 * that reaches toHtml() unescaped is an XSS hole.
 */
class QuranCitationTest extends TestCase
{
    public function test_a_braced_quotation_becomes_a_link_to_the_ayah(): void
    {
        $html = QuranCitation::toHtml('قَالَ [quran sura="4" aya_start="95" aya_end="95"]{لاَ يَسْتَوِي}');

        $this->assertStringContainsString(route('public.quran.verse', '4:95'), $html);
        $this->assertStringContainsString('{لاَ يَسْتَوِي}', $html);
        // The marker itself must be gone, not merely hidden.
        $this->assertStringNotContainsString('[quran', $html);
        $this->assertStringNotContainsString('aya_start', $html);
    }

    public function test_a_parenthesised_quotation_is_linked_and_keeps_its_delimiters(): void
    {
        $html = QuranCitation::toHtml('[quran sura="2" aya_start="255" aya_end="255"](اللَّهُ لاَ إِلَهَ إِلاَّ هُوَ)');

        $this->assertStringContainsString(route('public.quran.verse', '2:255'), $html);
        $this->assertStringContainsString('(اللَّهُ لاَ إِلَهَ إِلاَّ هُوَ)', $html);
        $this->assertStringNotContainsString('[quran', $html);
    }

    public function test_right_to_left_marks_between_the_marker_and_the_quotation_are_tolerated(): void
    {
        // About one marker in twenty carries a U+200F before the brace.
        $html = QuranCitation::toHtml("[quran sura=\"96\" aya_start=\"1\" aya_end=\"3\"]\u{200F}{\u{200F}اقْرَأْ}");

        $this->assertStringContainsString(route('public.quran.verse', '96:1'), $html);
        $this->assertStringNotContainsString('[quran', $html);
    }

    public function test_a_range_links_to_its_first_ayah_and_labels_the_span(): void
    {
        $html = QuranCitation::toHtml('[quran sura="74" aya_start="1" aya_end="5"]{يَا أَيُّهَا الْمُدَّثِّرُ}');

        $this->assertStringContainsString(route('public.quran.verse', '74:1'), $html);
        // Arabic-Indic digits, en dash between the two ends of the range.
        $this->assertStringContainsString('٧٤:١–٥', $html);
    }

    public function test_a_marker_quoting_nothing_still_renders_a_reference_link(): void
    {
        $html = QuranCitation::toHtml('فَنَزَلَتْ [quran sura="17" aya_start="85" aya_end="85"] وَيَسْأَلُونَكَ');

        $this->assertStringContainsString(route('public.quran.verse', '17:85'), $html);
        $this->assertStringContainsString('﴿١٧:٨٥﴾', $html);
        $this->assertStringContainsString('وَيَسْأَلُونَكَ', $html);
        $this->assertStringNotContainsString('[quran', $html);
    }

    public function test_a_surah_outside_the_mushaf_is_not_linked(): void
    {
        $html = QuranCitation::toHtml('[quran sura="115" aya_start="1" aya_end="1"]{نص}');

        $this->assertStringNotContainsString('<a', $html);
        $this->assertStringNotContainsString('[quran', $html);
        // The quotation survives — only the bad marker is dropped.
        $this->assertStringContainsString('{نص}', $html);
    }

    public function test_surrounding_text_is_escaped(): void
    {
        $html = QuranCitation::toHtml('<script>alert(1)</script> [quran sura="1" aya_start="1" aya_end="1"]{بِسْمِ}');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        // The one tag it is allowed to emit is its own anchor.
        $this->assertSame(1, substr_count($html, '<a href='));
    }

    public function test_a_quotation_containing_markup_is_escaped_inside_the_link(): void
    {
        $html = QuranCitation::toHtml('[quran sura="1" aya_start="1" aya_end="1"]{<img src=x onerror=alert(1)>}');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function test_strip_removes_the_marker_and_keeps_the_quotation(): void
    {
        $plain = QuranCitation::strip('قَالَ [quran sura="4" aya_start="95" aya_end="95"]{لاَ يَسْتَوِي} ثُمَّ');

        $this->assertSame('قَالَ {لاَ يَسْتَوِي} ثُمَّ', $plain);
    }

    public function test_strip_does_not_glue_words_together_when_the_marker_quotes_nothing(): void
    {
        $plain = QuranCitation::strip('فَنَزَلَتْ [quran sura="17" aya_start="85" aya_end="85"] وَيَسْأَلُونَكَ');

        $this->assertSame('فَنَزَلَتْ وَيَسْأَلُونَكَ', $plain);
    }

    public function test_strip_and_to_html_tolerate_null_and_empty(): void
    {
        $this->assertSame('', QuranCitation::strip(null));
        $this->assertSame('', QuranCitation::strip(''));
        $this->assertSame('', QuranCitation::toHtml(null));
        $this->assertSame('', QuranCitation::toHtml(''));
    }

    public function test_text_without_a_marker_is_returned_escaped_and_otherwise_untouched(): void
    {
        $matn = 'حَدَّثَنَا عُبَيْدُ اللَّهِ بْنُ مُوسَى';

        $this->assertSame($matn, QuranCitation::strip($matn));
        $this->assertSame($matn, QuranCitation::toHtml($matn));
    }
}
