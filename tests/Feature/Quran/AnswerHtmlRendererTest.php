<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Services\AnswerHtmlRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AnswerHtmlRendererTest extends TestCase
{
    use RefreshDatabase;

    private function seedSurahs(): void
    {
        $rows = [
            ['id' => 1, 'name_ar' => 'سُورَةُ ٱلْفَاتِحَةِ', 'name_en' => 'Al-Faatiha', 'transliteration' => 'Al-Faatiha'],
            ['id' => 2, 'name_ar' => 'سُورَةُ البَقَرَةِ', 'name_en' => 'Al-Baqara', 'transliteration' => 'Al-Baqara'],
            // Uthmani name carries a Quranic annotation mark (U+06E1) — must still match "النحل".
            ['id' => 16, 'name_ar' => 'سُورَةُ النَّحۡلِ', 'name_en' => 'An-Nahl', 'transliteration' => 'An-Nahl'],
            ['id' => 29, 'name_ar' => 'سُورَةُ العَنكَبُوتِ', 'name_en' => 'Al-Ankaboot', 'transliteration' => 'Al-Ankaboot'],
        ];
        foreach ($rows as $row) {
            DB::table('quran_surahs')->insert(array_merge($row, [
                'revelation_place' => 'makkah', 'revelation_order' => $row['id'],
                'bismillah_pre' => true, 'verses_count' => 7, 'pages' => json_encode([1]),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function renderer(): AnswerHtmlRenderer
    {
        return app(AnswerHtmlRenderer::class);
    }

    public function test_it_renders_markdown_blocks_to_premium_html(): void
    {
        $html = $this->renderer()->render("## Title\n\n**bold** and a point.\n\n- one\n- two\n\n> quoted line");

        $this->assertStringContainsString('<h3>Title</h3>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<ul><li>one</li><li>two</li></ul>', $html);
        $this->assertStringContainsString('<blockquote>quoted line</blockquote>', $html);
    }

    public function test_it_linkifies_marker_references(): void
    {
        $html = $this->renderer()->render('Patience is praised [[2:153]] and [[2:155-157]].');

        $this->assertStringContainsString('href="'.url('/quran/2#v153').'"', $html);
        $this->assertStringContainsString('class="qa-verse"', $html);
        $this->assertStringContainsString('>2:155-157</a>', $html);
        $this->assertStringNotContainsString('[[', $html);
    }

    public function test_it_linkifies_arabic_named_references_with_arabic_indic_digits(): void
    {
        $this->seedSurahs();

        $html = $this->renderer()->render('قال تعالى في (البقرة: ١٥٣) إن الله مع الصابرين.');

        // The Arabic-Indic ayah number resolves to the latin anchor on our reader.
        $this->assertStringContainsString('href="'.url('/quran/2#v153').'"', $html);
        // The visible label keeps the original Arabic text.
        $this->assertStringContainsString('البقرة: ١٥٣</a>', $html);
    }

    public function test_it_links_two_verses_inside_one_parenthesis(): void
    {
        $this->seedSurahs();

        // Two surahs in one paren, Arabic-comma separated, second with a "و" conjunction.
        $html = $this->renderer()->render('وعلى ربهم يتوكلون (النحل: ٤٢، والعنكبوت: ٥٩).');

        $this->assertSame(2, substr_count($html, 'class="qa-verse"'), 'both verses must be linked');
        $this->assertStringContainsString('href="'.url('/quran/16#v42').'"', $html);
        $this->assertStringContainsString('href="'.url('/quran/29#v59').'"', $html);
    }

    public function test_it_strips_horizontal_rules_and_grounding_footer(): void
    {
        $html = $this->renderer()->render("Answer body.\n\n---\n\n**المصادر المعتمدة:** تفسير معارف القرآن");

        $this->assertStringContainsString('Answer body.', $html);
        $this->assertStringNotContainsString('---', $html);
        $this->assertStringNotContainsString('المصادر المعتمدة', $html);
    }

    public function test_it_escapes_html_in_the_source(): void
    {
        $html = $this->renderer()->render('Try <script>alert(1)</script> now');

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
