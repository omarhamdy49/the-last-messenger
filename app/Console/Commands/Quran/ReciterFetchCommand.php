<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Console\Commands\Quran\Concerns\ReportsImport;
use App\Domain\Quran\Import\Reciters\ReciterFetchService;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use Illuminate\Console\Command;

final class ReciterFetchCommand extends Command
{
    use ReportsImport;

    protected $signature = 'quran:reciter:fetch
        {resource : Local reciter resource id (recitation or chapter reciter)}
        {--data : Update name, style, qirat and info from the source}
        {--audio : Import the audio file URLs}
        {--timestamps : Import chapter verse timings}
        {--download : Store the audio files on the public disk}';

    protected $description = 'Fetch one reciter\'s data, audio files and timings from its source (Quran Foundation or mp3quran)';

    public function handle(ReciterFetchService $service, ResourceRepositoryInterface $resources): int
    {
        $this->raiseMemoryLimit();

        $resource = $resources->find((int) $this->argument('resource'));
        if ($resource === null) {
            $this->error('Resource not found.');

            return self::FAILURE;
        }

        $data = (bool) $this->option('data');
        $audio = (bool) $this->option('audio');
        $timestamps = (bool) $this->option('timestamps');
        if (! $data && ! $audio && ! $timestamps) {
            [$data, $audio, $timestamps] = [true, true, true];
        }

        return $this->runStep(fn () => $service->fetch($resource, $data, $audio, $timestamps, (bool) $this->option('download')));
    }
}
