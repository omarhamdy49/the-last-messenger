<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\DTOs\CreateContactMessageData;
use App\Domain\Content\Models\ContactMessage;
use App\Domain\Content\Repositories\ContactMessageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Contact Message Service
 *
 * Receives public contact-form submissions and powers the admin inbox.
 * Intentionally uncached — messages are mutable, low-volume admin data.
 */
final class ContactMessageService
{
    public function __construct(
        private readonly ContactMessageRepositoryInterface $repository,
    ) {}

    /**
     * Store a new message submitted from the public contact form.
     */
    public function create(CreateContactMessageData $message): ContactMessage
    {
        return $this->repository->create($message);
    }

    /**
     * Mark a message as read (idempotent). Returns false when missing.
     */
    public function markRead(int $id): bool
    {
        return $this->repository->markRead($id);
    }

    /**
     * Count unread messages (admin badge).
     */
    public function unreadCount(): int
    {
        return $this->repository->unreadCount();
    }

    /**
     * Paginate messages for the admin inbox, newest first.
     */
    public function paginate(int $perPage = 20, ?bool $isRead = null): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $isRead);
    }

    /**
     * Find one message for the admin detail view.
     */
    public function find(int $id): ?ContactMessage
    {
        return $this->repository->find($id);
    }

    /**
     * Mark a message as unread (idempotent). Returns false when missing.
     */
    public function markUnread(int $id): bool
    {
        return $this->repository->markUnread($id);
    }

    /**
     * Delete a message. Returns false when missing.
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
