<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domain\Shared\Models\BulkOperation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BulkOperationFinished extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param array<string, int|string> $summary */
    public function __construct(public readonly array $summary)
    {
        $this->afterCommit();
    }

    public static function forOperation(BulkOperation $operation, string $locale): self
    {
        // A small immutable payload: never serialize exported content into mail jobs.
        $notification = new self([
            'id' => $operation->id,
            'type' => $operation->operation_type->value,
            'module' => $operation->module,
            'status' => $operation->status->value,
            'total' => (int) $operation->total_items,
            'successful' => (int) $operation->success_count,
            'failed' => (int) $operation->failed_count,
        ]);

        return $notification->locale(in_array($locale, ['ar', 'en'], true) ? $locale : 'ar');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('bulk_operations.subject', ['id' => $this->summary['id']], $this->locale))
            ->view('emails.bulk-operation-finished', [
                'summary' => $this->summary,
                'locale' => $this->locale,
                'statusUrl' => route('admin.bulk-operations.status', $this->summary['id']),
            ]);
    }
}
