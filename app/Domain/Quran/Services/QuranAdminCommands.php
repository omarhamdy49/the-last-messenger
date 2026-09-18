<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Enums\SyncRunMode;
use App\Domain\Quran\Models\SyncRun;
use App\Domain\Quran\Models\SyncState;
use App\Domain\Quran\Repositories\SyncRepositoryInterface;
use App\Domain\Shared\Models\CommandRun;
use App\Domain\Shared\Services\ArtisanCommandService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Admin buttons that start Quran Foundation imports. Every command goes through the
 * whitelisted Artisan runner (config/artisan-commands.php), so it is queued, logged
 * as a CommandRun and visible in Settings → Commands.
 */
final class QuranAdminCommands
{
    public function __construct(
        private readonly ArtisanCommandService $commands,
        private readonly SyncRepositoryInterface $sync,
    ) {}

    /**
     * @param  array<string, mixed>  $options  option name (no dashes) => value; flags as true
     */
    public function queue(string $command, array $options, ?int $userId): CommandRun
    {
        $meta = $this->commands->find($command)
            ?? throw new InvalidArgumentException("{$command} is not a whitelisted command.");

        return $this->commands->start($meta, ['options' => $options], $userId);
    }

    /**
     * @return Collection<int, SyncState>
     */
    public function states(): Collection
    {
        return $this->sync->states();
    }

    /**
     * @return Collection<int, SyncRun>
     */
    public function recentRuns(int $limit = 20): Collection
    {
        return $this->sync->recentRuns($limit);
    }

    public function run(int $id): ?SyncRun
    {
        return $this->sync->findRun($id);
    }

    /**
     * The SyncRun a reciter fetch job reports into (mode `fetch`, filter `reciter:{id}`).
     */
    public function startFetchRun(int $resourceId, ?int $userId): SyncRun
    {
        return $this->sync->startRun(SyncRunMode::FETCH, 'reciter:'.$resourceId, $userId);
    }

    public function latestFetchRun(int $resourceId): ?SyncRun
    {
        return $this->sync->latestRunFor('reciter:'.$resourceId);
    }
}
