<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Quran\Models\RecordingSource;
use App\Domain\Quran\Services\ReciterGroupingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GroupQuranRecitersCommand extends Command
{
    protected $signature = 'quran:group-reciters {--dry-run : Report person mappings without writing}';

    protected $description = 'Attach recording variants to canonical people using verified provider identities';

    public function handle(ReciterGroupingService $grouping): int
    {
        $lock = Cache::lock('quran:group-reciters', 600);
        if (! $lock->get()) {
            $this->error('Another grouping run is active.');

            return self::FAILURE;
        }
        try {
            $identities = json_decode(file_get_contents(database_path('data/quran-source-identities.json')), true, flags: JSON_THROW_ON_ERROR);
            if (! $this->option('dry-run')) {
                RecordingSource::where('provider', 'mp3quran')->each(function ($source) use ($identities): void {
                    if ($known = $identities[$source->base_url] ?? null) {
                        $source->update(['external_reciter_id' => $known['external_reciter_id'], 'external_recording_id' => $known['external_recording_id']]);
                    }
                });
            }
            $groups = [];
            Reciter::recitationsOnly()->with('translations')->orderBy('id')->chunkById(100, function ($recordings) use ($grouping, &$groups): void {
                foreach ($recordings as $recording) {
                    $identity = $grouping->identity($recording);
                    $groups[$identity['key']][] = $recording->slug;
                    if (! $this->option('dry-run')) {
                        $grouping->attach($recording);
                    }
                }
            });
            if (! $this->option('dry-run')) {
                foreach (['mp3quran:112' => 'minshawy-murattal', 'mp3quran:118' => 'husary', 'mp3quran:51' => 'abdul-basit-murattal'] as $key => $slug) {
                    $profile = ReciterProfile::where('identity_key', $key)->first();
                    $default = $profile?->recordings()->where('slug', $slug)->first();
                    if ($default) {
                        $profile->update(['default_recording_id' => $default->id]);
                    }
                }
            }
            foreach ($groups as $key => $slugs) {
                $this->line($key.' => '.implode(', ', $slugs));
            }
            $this->info(count($groups).' people; '.array_sum(array_map('count', $groups)).' recordings.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
