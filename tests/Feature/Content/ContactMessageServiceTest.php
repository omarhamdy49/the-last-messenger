<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\DTOs\CreateContactMessageData;
use App\Domain\Content\Services\ContactMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContactMessageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ContactMessageService::class);
    }

    private function createMessage(): \App\Domain\Content\Models\ContactMessage
    {
        return $this->service->create(new CreateContactMessageData(
            name: 'Omar',
            email: 'omar@example.com',
            subject: 'تنبيه على خطأ في صفحة الحديث',
            body: 'وجدت خطأ في رقم الحديث في الصفحة الفلانية.',
        ));
    }

    public function test_creates_message_from_dto_as_unread(): void
    {
        $message = $this->createMessage();

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'email' => 'omar@example.com',
            'is_read' => false,
        ]);
        $this->assertNull($message->read_at);
    }

    public function test_mark_read_sets_flag_and_timestamp(): void
    {
        $message = $this->createMessage();

        $this->assertTrue($this->service->markRead($message->id));

        $message->refresh();
        $this->assertTrue($message->is_read);
        $this->assertNotNull($message->read_at);
    }

    public function test_mark_read_is_idempotent_and_false_for_missing_id(): void
    {
        $message = $this->createMessage();

        $this->assertTrue($this->service->markRead($message->id));
        $this->assertTrue($this->service->markRead($message->id));
        $this->assertFalse($this->service->markRead(999999));
    }

    public function test_unread_count_reflects_read_state(): void
    {
        $first = $this->createMessage();
        $this->createMessage();

        $this->assertSame(2, $this->service->unreadCount());

        $this->service->markRead($first->id);

        $this->assertSame(1, $this->service->unreadCount());
    }

    public function test_paginate_filters_by_read_state_newest_first(): void
    {
        $first = $this->createMessage();
        $this->createMessage();
        $this->service->markRead($first->id);

        $unread = $this->service->paginate(10, false);
        $read = $this->service->paginate(10, true);
        $all = $this->service->paginate(10);

        $this->assertSame(1, $unread->total());
        $this->assertSame(1, $read->total());
        $this->assertSame(2, $all->total());
    }
}
