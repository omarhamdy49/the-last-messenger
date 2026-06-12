<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Services\ContactMessageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin — read-only inbox for public contact-form submissions
 * (triage: read/unread + delete).
 */
class ContactMessageController extends Controller
{
    public function __construct(
        protected ContactMessageService $service,
    ) {}

    /**
     * List messages, newest first, optionally filtered by read state.
     */
    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();
        $isRead = match ($filter) {
            'read' => true,
            'unread' => false,
            default => null,
        };

        $perPage = min($request->integer('per_page', 20), 100);

        return view('admin.content.contact-messages.index', [
            'messages' => $this->service->paginate($perPage, $isRead)->withQueryString(),
            'unreadCount' => $this->service->unreadCount(),
            'activeFilter' => $isRead === null ? 'all' : $filter,
        ]);
    }

    /**
     * Show one message (and mark it read — opening it means it was seen).
     */
    public function show(int $contact_message): View
    {
        $message = $this->service->find($contact_message);

        abort_if($message === null, 404);

        if (! $message->is_read) {
            $this->service->markRead($message->id);
            $message->refresh();
        }

        return view('admin.content.contact-messages.show', ['message' => $message]);
    }

    /**
     * Mark a message as read.
     */
    public function markRead(int $contact_message): RedirectResponse
    {
        abort_unless($this->service->markRead($contact_message), 404);

        return back()->with('success', __('admin.content_message_marked_read'));
    }

    /**
     * Mark a message as unread.
     */
    public function markUnread(int $contact_message): RedirectResponse
    {
        abort_unless($this->service->markUnread($contact_message), 404);

        return back()->with('success', __('admin.content_message_marked_unread'));
    }

    /**
     * Delete a message.
     */
    public function destroy(int $contact_message): RedirectResponse
    {
        abort_unless($this->service->delete($contact_message), 404);

        return redirect()
            ->route('admin.content.contact-messages.index')
            ->with('success', __('admin.content_message_deleted'));
    }
}
