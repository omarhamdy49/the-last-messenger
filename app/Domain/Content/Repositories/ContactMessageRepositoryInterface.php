<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\DTOs\CreateContactMessageData;
use App\Domain\Content\Models\ContactMessage;
use Illuminate\Pagination\LengthAwarePaginator;

interface ContactMessageRepositoryInterface
{
    /**
     * Persist a new contact message from the public form.
     */
    public function create(CreateContactMessageData $message): ContactMessage;

    /**
     * Mark a message as read (idempotent). Returns false when missing.
     */
    public function markRead(int $id): bool;

    /**
     * Count unread messages.
     */
    public function unreadCount(): int;

    /**
     * Paginate messages for the admin inbox, newest first.
     */
    public function paginate(int $perPage = 20, ?bool $isRead = null): LengthAwarePaginator;

    /**
     * Find one message (admin detail view).
     */
    public function find(int $id): ?ContactMessage;

    /**
     * Mark a message as unread (idempotent). Returns false when missing.
     */
    public function markUnread(int $id): bool;

    /**
     * Delete a message. Returns false when missing.
     */
    public function delete(int $id): bool;
}
