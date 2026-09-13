<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Shared\Enums\BulkOperationStatusEnum;
use App\Domain\Shared\Enums\BulkOperationTypeEnum;
use App\Domain\Shared\Models\BulkOperation;
use App\Domain\Shared\Services\BulkOperationService;
use App\Jobs\ProcessBulkOperation;
use App\Models\Article;
use App\Models\User;
use App\Notifications\BulkOperationFinished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

final class RoadmapBulkOperationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function operation(array $attributes = []): BulkOperation
    {
        return BulkOperation::query()->create($attributes + [
            'operation_type' => 'publish', 'module' => 'articles', 'status' => 'pending',
            'created_by' => User::factory()->create()->id, 'total_items' => 1,
            'processed_count' => 0, 'success_count' => 0, 'failed_count' => 0,
            'item_ids' => [], 'notify_on_completion' => true,
        ]);
    }

    public function test_small_operation_honors_notification_opt_in_and_processes_enum_type(): void
    {
        $admin = $this->createAdminUser(['language' => 'ar']);
        $article = Article::factory()->create(['status' => 'draft']);

        $this->actingAs($admin)->postJson(route('admin.bulk-operations.store'), [
            'operation_type' => 'publish', 'module' => 'articles',
            'item_ids' => [$article->id], 'notify_on_completion' => true,
        ])->assertCreated();

        $operation = BulkOperation::query()->firstOrFail();
        $this->assertTrue($operation->notify_on_completion);
        $this->assertSame(BulkOperationStatusEnum::COMPLETED, $operation->status);
        $this->assertSame(1, $operation->success_count);
        Notification::assertSentTo($admin, BulkOperationFinished::class, fn ($mail) => $mail->summary['successful'] === 1 && $mail->locale === 'ar');
    }

    public function test_operation_without_opt_in_sends_no_notification(): void
    {
        $admin = $this->createAdminUser();
        $article = Article::factory()->create(['status' => 'draft']);

        app(BulkOperationService::class)->createOperation('publish', 'articles', [$article->id], $admin->id);

        Notification::assertNothingSent();
        $this->assertFalse(BulkOperation::query()->firstOrFail()->notify_on_completion);
    }

    public function test_large_operation_preserves_opt_in_until_the_worker_runs(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $operation = app(BulkOperationService::class)->createOperation(
            'publish', 'articles', range(1, 501), $user->id, notifyOnCompletion: true,
        );

        $this->assertSame(BulkOperationStatusEnum::PENDING, $operation->status);
        Queue::assertPushed(ProcessBulkOperation::class, fn ($job) => $job->operation->notify_on_completion);
        Notification::assertNothingSent();
    }

    public function test_notification_queue_failure_does_not_fail_a_completed_operation(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Queue unavailable'));
        $article = Article::factory()->create(['status' => 'draft']);
        $operation = $this->operation(['item_ids' => [$article->id]]);
        (new ProcessBulkOperation($operation))->handle(app(BulkOperationService::class));

        $this->assertSame(BulkOperationStatusEnum::COMPLETED, $operation->fresh()->status);
        $this->assertSame(1, $operation->fresh()->success_count);
    }

    public function test_export_operation_uses_enum_and_returns_result_data(): void
    {
        $article = Article::factory()->create();
        $operation = $this->operation(['operation_type' => 'export', 'item_ids' => [$article->id]]);
        app(BulkOperationService::class)->processOperation($operation);

        $this->assertSame(BulkOperationStatusEnum::COMPLETED, $operation->status);
        $this->assertNotEmpty($operation->result_data['data']);
    }

    public function test_enum_status_helpers_and_job_tags_work(): void
    {
        $operation = $this->operation();
        $this->assertTrue($operation->canBeCancelled());
        $this->assertFalse($operation->isComplete());
        $this->assertContains('type:publish', (new ProcessBulkOperation($operation))->tags());

        foreach (BulkOperationStatusEnum::cases() as $status) {
            $operation->status = $status;
            $this->assertSame($status->isFinal(), $operation->isComplete());
            $this->assertSame($status->isInProgress(), $operation->canBeCancelled());
        }
        $this->assertSame(BulkOperationTypeEnum::PUBLISH, $operation->operation_type);
    }

    public function test_fractional_job_duration_is_returned_as_integer(): void
    {
        $this->travelTo(now()->startOfSecond()->addMicroseconds(500000));
        $operation = $this->operation();
        $operation->started_at = now()->startOfSecond()->subSeconds(3);
        $this->assertSame(3, $operation->getDuration());
    }

    public function test_preview_does_not_send_a_completion_email(): void
    {
        $article = Article::factory()->create();
        $operation = $this->operation(['dry_run' => true, 'item_ids' => [$article->id]]);
        (new ProcessBulkOperation($operation))->handle(app(BulkOperationService::class));

        Notification::assertNothingSent();
    }

    public function test_failure_before_any_records_exposes_error_details_to_the_owner(): void
    {
        $admin = $this->createAdminUser();
        $operation = $this->operation(['created_by' => $admin->id]);
        $operation->markAsFailed('Worker could not start');

        $this->actingAs($admin)->getJson(route('admin.bulk-operations.status', $operation->id))
            ->assertOk()
            ->assertJsonPath('data.error_log.0.message', 'Worker could not start');
    }

    public function test_retry_does_not_send_failure_email_before_final_failure(): void
    {
        $operation = $this->operation();
        $service = $this->mock(BulkOperationService::class);
        $service->shouldReceive('processOperation')->once()->andThrow(new RuntimeException('Import unavailable'));
        $job = new ProcessBulkOperation($operation);

        try {
            $job->handle($service);
            $this->fail('Expected operation failure');
        } catch (RuntimeException $exception) {
            Notification::assertNothingSent();
            $job->failed($exception);
        }

        Notification::assertSentTo($operation->creator, BulkOperationFinished::class, fn ($mail) => $mail->summary['status'] === 'failed');
        Notification::assertCount(1);
    }

    public function test_notification_delivery_is_queued_with_a_small_snapshot(): void
    {
        Notification::swap(new ChannelManager($this->app));
        Queue::fake();
        $operation = $this->operation(['status' => 'completed', 'success_count' => 1, 'result_data' => ['data' => 'private export']]);
        $operation->creator->notify(BulkOperationFinished::forOperation($operation, 'fr'));

        Queue::assertPushed(SendQueuedNotifications::class, function ($job): bool {
            $this->assertSame('ar', $job->notification->locale);
            $this->assertArrayNotHasKey('result_data', $job->notification->summary);

            return $job->notification->summary['successful'] === 1;
        });
    }

    public function test_mail_template_renders_in_arabic_and_english(): void
    {
        $operation = $this->operation(['status' => 'partially_completed', 'failed_count' => 1]);
        foreach (['ar' => 'rtl', 'en' => 'ltr'] as $locale => $direction) {
            $mail = BulkOperationFinished::forOperation($operation, $locale)->toMail($operation->creator);
            $html = view($mail->view, $mail->viewData)->render();
            $this->assertStringContainsString('dir="'.$direction.'"', $html);
            $this->assertStringContainsString(__('bulk_operations.statuses.partially_completed', [], $locale), $html);
            $this->assertStringNotContainsString('bulk_operations.', $html);
        }
    }
}
