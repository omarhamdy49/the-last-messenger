<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Content\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the public contact form: page render, successful submission through
 * ContactMessageService, field validation (422), the honeypot rejection, and
 * the POST rate limit.
 */
final class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'عمر',
            'email' => 'omar@example.com',
            'subject' => 'استفسار عن المحتوى',
            // StoreContactMessageRequest deliberately whitelists letters, digits, spaces
            // and `? - _ !` only — blocking `. : / @` is what kills the URLs in lead-gen
            // spam (see the rule's own comment). This payload previously contained `،`
            // and a full stop and so was rejected once that rule landed.
            'message' => 'السلام عليكم لدي سؤال حول مصادر الأحاديث المنشورة',
        ];
    }

    public function test_contact_page_renders(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/contact');

        $response->assertOk();
        $response->assertSee('تواصل معنا');
        $response->assertSee('name="website"', false); // honeypot present
    }

    public function test_contact_page_renders_in_english(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/contact')
            ->assertOk()
            ->assertSee('Contact Us');
    }

    public function test_valid_submission_stores_message_and_flashes_success(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->post('/contact', $this->validPayload());

        $response->assertRedirect(route('public.contact'));
        $response->assertSessionHas('toast');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'omar@example.com',
            'subject' => 'استفسار عن المحتوى',
            'is_read' => false,
        ]);
    }

    /**
     * Pins the anti-spam intent so a future "let users write normal punctuation" change is
     * a deliberate decision rather than an accident. The cost is real — a visitor cannot
     * end a sentence with a full stop — and is the owner's call, not this test's.
     */
    public function test_message_containing_a_url_is_rejected(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->post('/contact', [...$this->validPayload(), 'message' => 'Visit https://spam.example.com now'])
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_missing_fields_return_422(): void
    {
        $response = $this->postJson('/contact', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_invalid_email_returns_422(): void
    {
        $response = $this->postJson('/contact', array_merge(
            $this->validPayload(),
            ['email' => 'not-an-email'],
        ));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_filled_honeypot_rejects_submission(): void
    {
        $response = $this->postJson('/contact', array_merge(
            $this->validPayload(),
            ['website' => 'https://spam.example.com'],
        ));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['website']);

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_contact_post_is_rate_limited(): void
    {
        // throttle:10,1 — the 11th request within a minute must be rejected.
        foreach (range(1, 10) as $attempt) {
            $this->postJson('/contact', [])->assertStatus(422);
        }

        $this->postJson('/contact', $this->validPayload())->assertStatus(429);

        $this->assertSame(0, ContactMessage::query()->count());
    }
}
