<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Infrastructure\Llm\ChatClientInterface;
use App\Domain\Quran\Infrastructure\Llm\ChatResult;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AskQuranEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function bindFakeClients(): void
    {
        $this->app->instance(ChatClientInterface::class, new class implements ChatClientInterface
        {
            public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
            {
                return new ChatResult(
                    message: ['role' => 'assistant', 'content' => 'A grounded answer about patience [[2:155]].'],
                    usage: ['prompt_tokens' => 900, 'completion_tokens' => 150, 'prompt_cache_hit_tokens' => 700, 'prompt_cache_miss_tokens' => 200],
                );
            }

            public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
            {
                $content = 'A grounded answer about patience [[2:155]].';
                $onContentDelta($content);

                return new ChatResult(
                    message: ['role' => 'assistant', 'content' => $content],
                    usage: ['prompt_tokens' => 900, 'completion_tokens' => 150, 'prompt_cache_hit_tokens' => 700, 'prompt_cache_miss_tokens' => 200],
                );
            }
        });

        $this->app->instance(QuranMcpClientInterface::class, new class implements QuranMcpClientInterface
        {
            public function grounding(): array
            {
                return ['rules' => 'RULES', 'nonce' => 'gnd-test'];
            }

            public function toolDefinitions(): array
            {
                return [];
            }

            public function callTool(string $name, array $arguments): string
            {
                return '';
            }
        });
    }

    public function test_page_renders_for_guests_with_login_cta(): void
    {
        $this->get('/quran/ask')
            ->assertOk()
            ->assertSee(__('quran.public.ask_login_required'));
    }

    public function test_page_renders_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/quran/ask')
            ->assertOk();
    }

    public function test_guests_cannot_submit(): void
    {
        $this->postJson('/quran/ask', ['question' => 'What does the Quran say about patience?'])
            ->assertUnauthorized();
    }

    public function test_validation_rejects_too_short_questions(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/quran/ask', ['question' => 'hi'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('question');
    }

    public function test_authenticated_user_gets_a_grounded_answer(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindFakeClients();

        $this->actingAs(User::factory()->create())
            ->postJson('/quran/ask', ['question' => 'What does the Quran say about patience and trials?'])
            ->assertOk()
            ->assertJson(['source' => 'llm', 'remaining' => 9, 'limit' => 10])
            ->assertJsonStructure(['answer', 'citations', 'verses', 'source', 'remaining', 'limit'])
            // The [[2:155]] marker in the answer is surfaced as a structured verse link.
            ->assertJsonPath('verses.0.key', '2:155')
            ->assertJsonPath('verses.0.url', fn ($url): bool => str_contains((string) $url, '/quran/2#v155'));
    }

    public function test_streaming_endpoint_emits_tokens_and_a_done_event(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindFakeClients();

        $response = $this->actingAs(User::factory()->create())
            ->post('/quran/ask/stream', ['question' => 'What does the Quran say about patience and trials?'], ['Accept' => 'text/event-stream']);

        $response->assertOk();
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));

        $body = $response->streamedContent();
        $this->assertStringContainsString('"type":"delta"', $body);
        $this->assertStringContainsString('"type":"done"', $body);
        $this->assertStringContainsString('"source":"llm"', $body);
        $this->assertStringContainsString('2:155', $body); // referenced verse in the done event

        $this->assertDatabaseHas('ai_inquiries', ['source' => 'llm']);
    }

    public function test_geo_headers_are_whitelisted_and_stored(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindFakeClients();

        $this->actingAs(User::factory()->create())
            ->withHeaders(['X-Geo-Country' => 'eg', 'X-Geo-City' => 'Cairo', 'X-Geo-Lat' => '30.0'])
            ->postJson('/quran/ask', ['question' => 'What does the Quran say about gratitude?'])
            ->assertOk();

        $geo = \App\Domain\Quran\Models\AiInquiry::query()->latest('id')->first()->geo;
        $this->assertSame('EG', $geo['country']);   // upper-cased
        $this->assertSame('Cairo', $geo['city']);
        $this->assertArrayNotHasKey('lat', $geo);    // only country + city are kept
    }

    public function test_malformed_geo_country_is_dropped(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindFakeClients();

        $this->actingAs(User::factory()->create())
            ->withHeaders(['X-Geo-Country' => '1!', 'X-Geo-City' => 'Cairo'])
            ->postJson('/quran/ask', ['question' => 'What does the Quran say about kindness?'])
            ->assertOk();

        $geo = \App\Domain\Quran\Models\AiInquiry::query()->latest('id')->first()->geo;
        $this->assertArrayNotHasKey('country', $geo); // invalid format rejected
        $this->assertSame('Cairo', $geo['city']);
    }

    public function test_question_is_sanitized_before_persistence(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindFakeClients();

        $this->actingAs(User::factory()->create())
            ->postJson('/quran/ask', ['question' => "What about patience'; DROP TABLE users; <b>x</b>?"])
            ->assertOk();

        $stored = \App\Domain\Quran\Models\AiInquiry::query()->latest('id')->first()->question;
        $this->assertStringNotContainsString("'", $stored);
        $this->assertStringNotContainsString(';', $stored);
        $this->assertStringNotContainsString('<', $stored);
        $this->assertStringContainsString('patience', $stored);
    }

    public function test_returns_429_when_monthly_quota_is_exhausted(): void
    {
        config()->set('services.quran_ai.monthly_quota', 1);
        $this->bindFakeClients();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/quran/ask', ['question' => 'A first unique question about charity'])
            ->assertOk();

        $this->actingAs($user)
            ->postJson('/quran/ask', ['question' => 'A second unique question about fasting'])
            ->assertStatus(429)
            ->assertJson(['remaining' => 0]);
    }
}
