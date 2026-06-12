<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Content;

use App\Domain\Content\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.content.faqs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_faq_with_sanitized_answer(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.content.faqs.store'), [
            'category' => 'general',
            'is_active' => '1',
            'question' => ['ar' => 'ما هو المشروع؟'],
            'answer' => ['ar' => '<p>موسوعة <strong>إسلامية</strong></p><script>alert(1)</script>'],
        ])->assertRedirect(route('admin.content.faqs.index'));

        $faq = Faq::query()->with('translations')->firstOrFail();
        $answer = $faq->getTranslation('answer', 'ar');

        $this->assertSame('general', $faq->category);
        $this->assertStringNotContainsString('<script', $answer);
        $this->assertStringContainsString('<strong>إسلامية</strong>', $answer);
    }

    public function test_store_validation_requires_arabic_question_and_answer(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->postJson(route('admin.content.faqs.store'), [
            'question' => ['en' => 'Only English'],
            'answer' => [],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['question.ar', 'answer.ar']);
    }

    public function test_admin_can_update_and_delete_faq(): void
    {
        $this->actingAs($this->createAdminUser());

        $faq = Faq::query()->create(['category' => 'general', 'is_active' => true, 'sort_order' => 0]);
        $faq->syncTranslations([
            'question' => ['ar' => 'سؤال'],
            'answer' => ['ar' => '<p>جواب</p>'],
        ]);

        $this->put(route('admin.content.faqs.update', $faq->id), [
            'category' => 'seerah',
            'is_active' => '1',
            'question' => ['ar' => 'سؤال محدث'],
            'answer' => ['ar' => '<p>جواب محدث</p>'],
        ])->assertRedirect(route('admin.content.faqs.index'));

        $faq->refresh()->load('translations');
        $this->assertSame('seerah', $faq->category);
        $this->assertSame('سؤال محدث', $faq->getTranslation('question', 'ar'));

        $this->delete(route('admin.content.faqs.destroy', $faq->id))->assertRedirect();
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}
