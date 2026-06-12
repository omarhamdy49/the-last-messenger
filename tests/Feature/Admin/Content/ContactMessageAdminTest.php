<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Content;

use App\Domain\Content\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageAdminTest extends TestCase
{
    use RefreshDatabase;

    private function createMessage(array $attributes = []): ContactMessage
    {
        return ContactMessage::query()->create(array_merge([
            'name' => 'زائر الموقع',
            'email' => 'visitor@example.com',
            'subject' => 'استفسار عن السيرة',
            'body' => 'السلام عليكم، لدي سؤال.',
        ], $attributes));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.content.contact-messages.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_inbox_with_unread_filter(): void
    {
        $this->actingAs($this->createAdminUser());

        $unread = $this->createMessage();
        $read = $this->createMessage([
            'subject' => 'رسالة مقروءة', 'is_read' => true, 'read_at' => now(),
        ]);

        $this->get(route('admin.content.contact-messages.index'))
            ->assertOk()
            ->assertSee($unread->subject)
            ->assertSee($read->subject);

        $this->get(route('admin.content.contact-messages.index', ['filter' => 'unread']))
            ->assertOk()
            ->assertSee($unread->subject)
            ->assertDontSee($read->subject);
    }

    public function test_opening_a_message_marks_it_read(): void
    {
        $this->actingAs($this->createAdminUser());

        $message = $this->createMessage();

        $this->get(route('admin.content.contact-messages.show', $message->id))
            ->assertOk()
            ->assertSee($message->subject);

        $this->assertTrue($message->refresh()->is_read);
        $this->assertNotNull($message->read_at);
    }

    public function test_admin_can_mark_message_unread(): void
    {
        $this->actingAs($this->createAdminUser());

        $message = $this->createMessage(['is_read' => true, 'read_at' => now()]);

        $this->post(route('admin.content.contact-messages.mark-unread', $message->id))
            ->assertRedirect();

        $this->assertFalse($message->refresh()->is_read);
        $this->assertNull($message->read_at);
    }

    public function test_admin_can_delete_message(): void
    {
        $this->actingAs($this->createAdminUser());

        $message = $this->createMessage();

        $this->delete(route('admin.content.contact-messages.destroy', $message->id))
            ->assertRedirect(route('admin.content.contact-messages.index'));

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }

    public function test_showing_missing_message_returns_404(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.content.contact-messages.show', 999999))->assertNotFound();
    }
}
