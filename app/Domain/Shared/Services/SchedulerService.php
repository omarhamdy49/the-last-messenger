<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Enums\ScheduleAction;
use App\Domain\Shared\Enums\ScheduleStatus;
use App\Domain\Shared\Models\ContentSchedule;
use App\Domain\Shared\Repositories\SchedulerRepositoryInterface;
use App\Exceptions\Domain\BusinessLogicException;
use App\Exceptions\Domain\ResourceNotFoundException;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchedulerService
{
    /**
     * SchedulerService constructor
     */
    public function __construct(
        protected SchedulerRepositoryInterface $repository
    ) {}

    /**
     * Get all scheduled content with pagination (delegation to repository)
     */
    protected function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $columns, $relations);
    }

    /**
     * Schedule content for publishing
     *
     * @throws BusinessLogicException
     */
    public function schedulePublish(
        Model $content,
        string $scheduledAt,
        int $userId,
        string $timezone = 'UTC',
        ?string $notes = null,
        ?array $recurrence = null
    ): ContentSchedule {
        // Check for existing pending publish schedule
        if ($this->repository->hasPendingSchedule(
            get_class($content),
            $content->id,
            ScheduleAction::PUBLISH
        )) {
            throw new BusinessLogicException('Content already has a pending publish schedule');
        }

        $data = [
            'schedulable_type' => get_class($content),
            'schedulable_id' => $content->id,
            'scheduled_at' => Carbon::parse($scheduledAt, $timezone)->setTimezone('UTC'),
            'action' => ScheduleAction::PUBLISH,
            'status' => ScheduleStatus::PENDING,
            'timezone' => $timezone,
            'created_by' => $userId,
            'notes' => $notes,
        ];

        // Add recurrence settings if provided
        if ($recurrence) {
            $data = array_merge($data, $this->buildRecurrenceData($recurrence));
        }

        return $this->repository->createSchedule($data);
    }

    /**
     * Schedule content for unpublishing
     *
     * @throws BusinessLogicException
     */
    public function scheduleUnpublish(
        Model $content,
        string $scheduledAt,
        int $userId,
        string $timezone = 'UTC',
        ?string $notes = null
    ): ContentSchedule {
        // Check for existing pending unpublish schedule
        if ($this->repository->hasPendingSchedule(
            get_class($content),
            $content->id,
            ScheduleAction::UNPUBLISH
        )) {
            throw new BusinessLogicException('Content already has a pending unpublish schedule');
        }

        $data = [
            'schedulable_type' => get_class($content),
            'schedulable_id' => $content->id,
            'scheduled_at' => Carbon::parse($scheduledAt, $timezone)->setTimezone('UTC'),
            'action' => ScheduleAction::UNPUBLISH,
            'status' => ScheduleStatus::PENDING,
            'timezone' => $timezone,
            'created_by' => $userId,
            'notes' => $notes,
        ];

        return $this->repository->createSchedule($data);
    }

    /**
     * Schedule content update
     *
     * @throws BusinessLogicException
     */
    public function scheduleUpdate(
        Model $content,
        array $updateData,
        string $scheduledAt,
        int $userId,
        string $timezone = 'UTC',
        ?string $notes = null
    ): ContentSchedule {
        if (empty($updateData)) {
            throw new BusinessLogicException('Update data cannot be empty');
        }

        $data = [
            'schedulable_type' => get_class($content),
            'schedulable_id' => $content->id,
            'scheduled_at' => Carbon::parse($scheduledAt, $timezone)->setTimezone('UTC'),
            'action' => ScheduleAction::UPDATE,
            'status' => ScheduleStatus::PENDING,
            'timezone' => $timezone,
            'scheduled_data' => $updateData,
            'created_by' => $userId,
            'notes' => $notes,
        ];

        return $this->repository->createSchedule($data);
    }

    /**
     * Get all scheduled content with pagination
     */
    public function getAllSchedules(int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginate($perPage, ['*'], ['schedulable', 'creator']);
    }

    /**
     * Get schedules by status
     */
    public function getSchedulesByStatus(ScheduleStatus|string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByStatus($status, $perPage);
    }

    /**
     * Get schedules by action
     */
    public function getSchedulesByAction(ScheduleAction|string $action, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByAction($action, $perPage);
    }

    /**
     * Get schedule by ID
     *
     * @throws ResourceNotFoundException
     */
    public function getScheduleById(int $id): ContentSchedule
    {
        $schedule = $this->repository->find($id, ['*'], ['schedulable', 'creator']);

        if (! $schedule) {
            throw new ResourceNotFoundException("Schedule not found with ID: {$id}");
        }

        return $schedule;
    }

    /**
     * Get schedule history for content
     */
    public function getScheduleHistory(string $type, int $id, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getHistoryForContent($type, $id, $perPage);
    }

    /**
     * Get upcoming schedules
     */
    public function getUpcomingSchedules(int $limit = 10): Collection
    {
        return $this->repository->getUpcoming($limit);
    }

    /**
     * Update schedule
     *
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updateSchedule(int $id, array $data): ContentSchedule
    {
        $schedule = $this->getScheduleById($id);

        if (! $schedule->canBeUpdated()) {
            throw new BusinessLogicException('Schedule cannot be updated in its current status');
        }

        // Parse scheduled_at if provided
        if (isset($data['scheduled_at'])) {
            $timezone = $data['timezone'] ?? $schedule->timezone;
            $data['scheduled_at'] = Carbon::parse($data['scheduled_at'], $timezone)->setTimezone('UTC');
        }

        // Handle recurrence updates
        if (isset($data['recurrence'])) {
            $data = array_merge($data, $this->buildRecurrenceData($data['recurrence']));
            unset($data['recurrence']);
        }

        $this->repository->updateSchedule($id, $data);

        return $this->getScheduleById($id);
    }

    /**
     * Cancel schedule
     *
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function cancelSchedule(int $id): bool
    {
        $schedule = $this->getScheduleById($id);

        if (! $schedule->canBeCancelled()) {
            throw new BusinessLogicException('Schedule cannot be cancelled in its current status');
        }

        return $this->repository->cancelSchedule($id);
    }

    /**
     * Process scheduled content (called by console command)
     */
    public function processScheduledContent(): array
    {
        $dueSchedules = $this->repository->getDueSchedules();
        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($dueSchedules as $schedule) {
            $results['processed']++;

            try {
                $this->executeSchedule($schedule);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Schedule execution failed', [
                    'schedule_id' => $schedule->id,
                    'content_type' => $schedule->schedulable_type,
                    'content_id' => $schedule->schedulable_id,
                    'action' => $schedule->action->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Process retryable failed schedules
        $retryableSchedules = $this->repository->getRetryable();
        foreach ($retryableSchedules as $schedule) {
            $results['processed']++;

            try {
                $this->executeSchedule($schedule);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                    'retry_count' => $schedule->retry_count + 1,
                ];
            }
        }

        return $results;
    }

    /**
     * Execute a single schedule
     *
     * @throws \Exception
     */
    protected function executeSchedule(ContentSchedule $schedule): void
    {
        // Mark as processing
        $this->repository->markAsProcessing($schedule->id);

        try {
            DB::beginTransaction();

            $content = $schedule->schedulable;

            if (! $content) {
                throw new \Exception('Schedulable content not found');
            }

            // Execute action based on type
            match ($schedule->action) {
                ScheduleAction::PUBLISH => $this->executePublish($content),
                ScheduleAction::UNPUBLISH => $this->executeUnpublish($content),
                ScheduleAction::UPDATE => $this->executeUpdate($content, $schedule->scheduled_data ?? []),
            };

            DB::commit();

            // Mark as published
            $this->repository->markAsPublished($schedule->id);

            // Handle recurring schedules
            if ($schedule->isRecurring()) {
                $this->createNextRecurrence($schedule);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            // Mark as failed
            $this->repository->markAsFailed($schedule->id, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Execute publish action
     */
    protected function executePublish(Model $content): void
    {
        if (method_exists($content, 'publish')) {
            $content->publish();
        } elseif (property_exists($content, 'status')) {
            $content->update(['status' => 'published']);
        } else {
            throw new \Exception('Content does not support publish action');
        }
    }

    /**
     * Execute unpublish action
     */
    protected function executeUnpublish(Model $content): void
    {
        if (method_exists($content, 'unpublish')) {
            $content->unpublish();
        } elseif (property_exists($content, 'status')) {
            $content->update(['status' => 'draft']);
        } else {
            throw new \Exception('Content does not support unpublish action');
        }
    }

    /**
     * Execute update action
     */
    protected function executeUpdate(Model $content, array $data): void
    {
        if (empty($data)) {
            throw new \Exception('No update data provided');
        }

        $content->update($data);
    }

    /**
     * Create next recurrence schedule
     */
    protected function createNextRecurrence(ContentSchedule $schedule): void
    {
        // Check if recurrence has ended
        if ($schedule->recurrence_ends_at && now() >= $schedule->recurrence_ends_at) {
            return;
        }

        $nextScheduledAt = $this->calculateNextRecurrence(
            $schedule->scheduled_at,
            $schedule->recurrence_type,
            $schedule->recurrence_interval ?? 1,
            $schedule->recurrence_days
        );

        // Don't create if next occurrence is past end date
        if ($schedule->recurrence_ends_at && $nextScheduledAt >= $schedule->recurrence_ends_at) {
            return;
        }

        $this->repository->createSchedule([
            'schedulable_type' => $schedule->schedulable_type,
            'schedulable_id' => $schedule->schedulable_id,
            'scheduled_at' => $nextScheduledAt,
            'action' => $schedule->action,
            'status' => ScheduleStatus::PENDING,
            'timezone' => $schedule->timezone,
            'recurrence_type' => $schedule->recurrence_type,
            'recurrence_interval' => $schedule->recurrence_interval,
            'recurrence_ends_at' => $schedule->recurrence_ends_at,
            'recurrence_days' => $schedule->recurrence_days,
            'scheduled_data' => $schedule->scheduled_data,
            'created_by' => $schedule->created_by,
            'notes' => $schedule->notes,
        ]);
    }

    /**
     * Calculate next recurrence date
     */
    protected function calculateNextRecurrence(
        Carbon $current,
        string $type,
        int $interval,
        ?array $days = null
    ): Carbon {
        return match ($type) {
            'daily' => $current->copy()->addDays($interval),
            'weekly' => $current->copy()->addWeeks($interval),
            'monthly' => $current->copy()->addMonths($interval),
            'yearly' => $current->copy()->addYears($interval),
            default => throw new \InvalidArgumentException("Invalid recurrence type: {$type}"),
        };
    }

    /**
     * Build recurrence data array
     */
    protected function buildRecurrenceData(array $recurrence): array
    {
        $data = [];

        if (isset($recurrence['type'])) {
            $data['recurrence_type'] = $recurrence['type'];
        }

        if (isset($recurrence['interval'])) {
            $data['recurrence_interval'] = (int) $recurrence['interval'];
        }

        if (isset($recurrence['ends_at'])) {
            $data['recurrence_ends_at'] = Carbon::parse($recurrence['ends_at']);
        }

        if (isset($recurrence['days'])) {
            $data['recurrence_days'] = is_array($recurrence['days'])
                ? $recurrence['days']
                : json_decode($recurrence['days'], true);
        }

        return $data;
    }

    /**
     * Get scheduler statistics
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Search schedules with filters
     */
    public function searchSchedules(array $filters): LengthAwarePaginator
    {
        return $this->repository->search($filters);
    }
}
