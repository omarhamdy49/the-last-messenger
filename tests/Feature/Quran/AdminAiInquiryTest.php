<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\AiInquiry;
use App\Domain\Quran\Repositories\AiInquiryRepositoryInterface;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class AdminAiInquiryTest extends TestCase
{
    use RefreshDatabase;

    private function makeInquiry(array $overrides = []): AiInquiry
    {
        $user = User::factory()->create();

        return AiInquiry::create(array_merge([
            'user_id' => $user->id,
            'locale' => 'en',
            'question' => 'What does the Quran say about patience?',
            'normalized_question' => 'what does the quran say about patience?',
            'question_hash' => str_repeat('a', 64),
            'answer' => 'Original grounded answer.',
            'source' => AiInquiry::SOURCE_LLM,
            'cost_usd' => 0.0016,
        ], $overrides));
    }

    public function test_non_admin_is_redirected_away_from_moderation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.quran.ai-inquiries.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_the_inquiry_list(): void
    {
        $this->makeInquiry();

        $this->actingAs($this->createAdminUser())
            ->get(route('admin.quran.ai-inquiries.index'))
            ->assertOk()
            ->assertSee('patience');
    }

    public function test_admin_editing_the_answer_invalidates_the_answer_cache(): void
    {
        $inquiry = $this->makeInquiry();
        $cacheKey = QuranCacheKeys::aiAnswer($inquiry->question_hash, $inquiry->locale);
        Cache::put($cacheKey, ['answer' => 'Original grounded answer.', 'citations' => []], now()->addDay());

        $this->actingAs($this->createAdminUser())
            ->put(route('admin.quran.ai-inquiries.answer', $inquiry->id), [
                'question' => 'What is the virtue of patience?',
                'answer' => '<p>Corrected answer.</p>',
            ])
            ->assertRedirect(route('admin.quran.ai-inquiries.show', $inquiry->id));

        $inquiry->refresh();
        $this->assertSame('What is the virtue of patience?', $inquiry->question);
        $this->assertSame('<p>Corrected answer.</p>', $inquiry->answer);
        $this->assertNotNull($inquiry->answer_edited_at);
        $this->assertNull(Cache::get($cacheKey), 'stale cached answer must be invalidated');
    }

    public function test_admin_can_publish_an_inquiry_as_a_faq(): void
    {
        $inquiry = $this->makeInquiry();

        $this->actingAs($this->createAdminUser())
            ->post(route('admin.quran.ai-inquiries.publish', $inquiry->id), [
                'category' => 'general',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.quran.ai-inquiries.show', $inquiry->id));

        $this->assertDatabaseHas('faqs', ['category' => 'general', 'is_active' => true]);
        $this->assertDatabaseHas('faq_translations', ['locale' => 'en', 'question' => $inquiry->question]);

        $inquiry->refresh();
        $this->assertNotNull($inquiry->published_faq_id);
    }

    public function test_categories_endpoint_returns_existing_faq_categories(): void
    {
        $inquiry = $this->makeInquiry();
        // Publish once to create an FAQ with a category.
        $this->actingAs($this->createAdminUser())
            ->post(route('admin.quran.ai-inquiries.publish', $inquiry->id), ['category' => 'العبادات', 'is_active' => '1']);

        $this->actingAs($this->createAdminUser())
            ->getJson(route('admin.quran.ai-inquiries.categories'))
            ->assertOk()
            ->assertJsonStructure(['categories'])
            ->assertJsonFragment(['العبادات']);
    }

    public function test_can_publish_via_json_from_the_list(): void
    {
        $inquiry = $this->makeInquiry();

        $this->actingAs($this->createAdminUser())
            ->postJson(route('admin.quran.ai-inquiries.publish', $inquiry->id), ['category' => 'الإيمان', 'is_active' => 1])
            ->assertOk()
            ->assertJsonStructure(['published_faq_id', 'message']);

        $this->assertDatabaseHas('faqs', ['category' => 'الإيمان', 'is_active' => true]);
        $this->assertNotNull($inquiry->fresh()->published_faq_id);
    }

    public function test_publishing_twice_does_not_create_a_duplicate_faq(): void
    {
        $inquiry = $this->makeInquiry();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->post(route('admin.quran.ai-inquiries.publish', $inquiry->id), ['is_active' => '1']);
        $this->actingAs($admin)->post(route('admin.quran.ai-inquiries.publish', $inquiry->id), ['is_active' => '1']);

        $this->assertDatabaseCount('faqs', 1);
    }

    public function test_admin_list_and_history_deduplicate_repeat_questions(): void
    {
        $user = User::factory()->create();
        // Same question asked twice → an LLM row + a cache-hit replay row.
        foreach ([AiInquiry::SOURCE_LLM, AiInquiry::SOURCE_CACHE] as $source) {
            AiInquiry::create([
                'user_id' => $user->id, 'locale' => 'en',
                'question' => 'What does the Quran say about patience?',
                'normalized_question' => 'what does the quran say about patience?',
                'question_hash' => str_repeat('b', 64),
                'answer' => 'Answer.', 'source' => $source,
            ]);
        }

        $repo = app(AiInquiryRepositoryInterface::class);
        $this->assertSame(1, $repo->paginateForAdmin(20)->total(), 'admin list must show one row per question');
        $this->assertCount(1, $repo->historyForUser($user->id), 'history must show one entry per question');
    }

    public function test_history_fetch_is_idor_safe(): void
    {
        $inquiry = $this->makeInquiry();

        // A different user cannot read it.
        $this->actingAs(User::factory()->create())
            ->getJson(route('public.quran.ask.history', $inquiry->id))
            ->assertNotFound();

        // The owner can.
        $this->actingAs($inquiry->user)
            ->getJson(route('public.quran.ask.history', $inquiry->id))
            ->assertOk()
            ->assertJsonStructure(['question', 'answer', 'citations', 'source']);
    }

    public function test_admin_can_soft_delete_an_inquiry(): void
    {
        $inquiry = $this->makeInquiry();

        $this->actingAs($this->createAdminUser())
            ->delete(route('admin.quran.ai-inquiries.destroy', $inquiry->id))
            ->assertRedirect(route('admin.quran.ai-inquiries.index'));

        $this->assertSoftDeleted('ai_inquiries', ['id' => $inquiry->id]);
    }
}
