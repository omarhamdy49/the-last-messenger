<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\Models\Faq;
use App\Domain\Content\Services\FaqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqServiceTest extends TestCase
{
    use RefreshDatabase;

    private FaqService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FaqService::class);
    }

    private function createFaq(array $attributes, array $question, array $answer): Faq
    {
        /** @var Faq $faq */
        $faq = Faq::query()->create(array_merge(['is_active' => true, 'sort_order' => 0], $attributes));
        $faq->syncTranslations(['question' => $question, 'answer' => $answer]);

        return $faq;
    }

    public function test_active_faqs_are_ordered_translated_and_filterable_by_category(): void
    {
        $this->createFaq(
            ['category' => 'account', 'sort_order' => 20],
            ['ar' => 'هل أحتاج إلى حساب؟', 'en' => 'Do I need an account?'],
            ['ar' => 'لا.', 'en' => 'No.']
        );
        $this->createFaq(
            ['category' => 'general', 'sort_order' => 10],
            ['ar' => 'ما هو المشروع؟'],
            ['ar' => 'موسوعة معرفية.']
        );
        $this->createFaq(['category' => 'general', 'sort_order' => 30, 'is_active' => false], ['ar' => 'مخفي'], ['ar' => 'مخفي']);

        $all = $this->service->active('en');

        $this->assertCount(2, $all);
        // Sort order respected; AR fallback when EN missing.
        $this->assertSame('ما هو المشروع؟', $all[0]['question']);
        $this->assertSame('Do I need an account?', $all[1]['question']);

        $account = $this->service->active('en', 'account');

        $this->assertCount(1, $account);
        $this->assertSame('account', $account[0]['category']);
    }

    public function test_flush_invalidates_cached_faqs(): void
    {
        $this->assertCount(0, $this->service->active('ar'));

        $this->createFaq(['category' => 'general', 'sort_order' => 10], ['ar' => 'سؤال'], ['ar' => 'جواب']);

        // Still cached until flushed.
        $this->assertCount(0, $this->service->active('ar'));

        $this->service->flush();

        $this->assertCount(1, $this->service->active('ar'));
    }
}
