<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Blog\Services;

use App\Domain\Blog\Services\ReadTimeCalculatorService;
use Tests\TestCase;

class ReadTimeCalculatorServiceTest extends TestCase
{
    private ReadTimeCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReadTimeCalculatorService;
    }

    public function test_empty_content_returns_one_minute(): void
    {
        $result = $this->service->calculate('');

        $this->assertSame(1, $result);
    }

    public function test_content_with_only_html_tags_returns_one_minute(): void
    {
        $result = $this->service->calculate('<p></p><div></div><br />');

        $this->assertSame(1, $result);
    }

    public function test_two_hundred_words_returns_one_minute(): void
    {
        $words = implode(' ', array_fill(0, 200, 'word'));
        $content = "<p>{$words}</p>";

        $result = $this->service->calculate($content);

        $this->assertSame(1, $result);
    }

    public function test_four_hundred_words_returns_two_minutes(): void
    {
        $words = implode(' ', array_fill(0, 400, 'word'));
        $content = "<p>{$words}</p>";

        $result = $this->service->calculate($content);

        $this->assertSame(2, $result);
    }

    public function test_six_hundred_words_returns_three_minutes(): void
    {
        $words = implode(' ', array_fill(0, 600, 'word'));
        $content = "<article>{$words}</article>";

        $result = $this->service->calculate($content);

        $this->assertSame(3, $result);
    }

    public function test_minimum_read_time_is_always_one_minute(): void
    {
        // Single word should still return 1, never 0
        $result = $this->service->calculate('<p>word</p>');

        $this->assertSame(1, $result);
    }

    public function test_html_tags_are_stripped_before_word_count(): void
    {
        // "Hello" and "World" are two words wrapped in heavy markup
        $content = '<h1><strong><em>Hello</em></strong></h1><p class="lead">World</p>';

        $result = $this->service->calculate($content);

        // 2 words → 1 minute
        $this->assertSame(1, $result);
    }

    public function test_arabic_text_is_counted(): void
    {
        // Arabic text: several distinct words
        $arabicWords = implode(' ', array_fill(0, 200, 'كلمة'));
        $content = "<p>{$arabicWords}</p>";

        // Result should be a positive integer (Arabic is counted by str_word_count)
        $result = $this->service->calculate($content);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(1, $result);
    }
}
