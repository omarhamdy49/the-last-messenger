<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\DTOs\CreateContactMessageData;
use App\Domain\Content\Models\ContactMessage;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of the contact messages repository.
 */
class ContactMessageRepository implements ContactMessageRepositoryInterface
{
    public function create(CreateContactMessageData $message): ContactMessage
    {
        return ContactMessage::query()->create([
            'name' => $message->name,
            'email' => $message->email,
            'subject' => $message->subject,
            'body' => $message->body,
        ]);
    }

    public function markRead(int $id): bool
    {
        $updated = ContactMessage::query()
            ->whereKey($id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        // Already-read messages count as success (idempotent mark).
        return $updated > 0 || ContactMessage::query()->whereKey($id)->where('is_read', true)->exists();
    }

    public function unreadCount(): int
    {
        return ContactMessage::query()->unread()->count();
    }

    public function paginate(int $perPage = 20, ?bool $isRead = null): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->select(['id', 'name', 'email', 'subject', 'is_read', 'read_at', 'created_at'])
            ->when($isRead !== null, fn ($query) => $query->where('is_read', $isRead))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function find(int $id): ?ContactMessage
    {
        return ContactMessage::query()
            ->select(['id', 'name', 'email', 'subject', 'body', 'is_read', 'read_at', 'created_at'])
            ->find($id);
    }

    public function markUnread(int $id): bool
    {
        $updated = ContactMessage::query()
            ->whereKey($id)
            ->where('is_read', true)
            ->update(['is_read' => false, 'read_at' => null]);

        // Already-unread messages count as success (idempotent mark).
        return $updated > 0 || ContactMessage::query()->whereKey($id)->where('is_read', false)->exists();
    }

    public function delete(int $id): bool
    {
        return ContactMessage::query()->whereKey($id)->delete() > 0;
    }
}
