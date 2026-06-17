<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Support\QuestionSanitizer;
use Tests\TestCase;

final class QuestionSanitizerTest extends TestCase
{
    public function test_it_keeps_letters_digits_and_allowed_punctuation(): void
    {
        $this->assertSame('What is sabr? Be patient!', QuestionSanitizer::sanitize('What is sabr? Be patient!'));
        $this->assertSame('well-being _note_ 2', QuestionSanitizer::sanitize('well-being _note_ 2'));
    }

    public function test_it_keeps_arabic_letters_marks_and_arabic_question_mark(): void
    {
        $input = 'ما فضلُ الصبرِ؟';

        $this->assertSame($input, QuestionSanitizer::sanitize($input));
    }

    public function test_it_strips_injection_and_other_special_characters(): void
    {
        $out = QuestionSanitizer::sanitize("patience'; DROP TABLE users; <script>alert(1)</script> & \"x\"");

        $this->assertStringNotContainsString("'", $out);
        $this->assertStringNotContainsString(';', $out);
        $this->assertStringNotContainsString('<', $out);
        $this->assertStringNotContainsString('>', $out);
        $this->assertStringNotContainsString('"', $out);
        $this->assertStringNotContainsString('&', $out);
        $this->assertStringNotContainsString('(', $out);
        $this->assertStringContainsString('patience', $out);
        // The result conforms to the allowlist contract.
        $this->assertMatchesRegularExpression(QuestionSanitizer::PATTERN, $out);
    }

    public function test_it_collapses_whitespace_left_by_stripping(): void
    {
        $this->assertSame('a b c', QuestionSanitizer::sanitize('a , b  ;  c'));
    }
}
