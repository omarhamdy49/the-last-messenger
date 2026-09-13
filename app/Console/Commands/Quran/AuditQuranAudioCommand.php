<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class AuditQuranAudioCommand extends Command
{
    protected $signature = 'quran:audio-audit {--snapshot= : Write a recovery snapshot to this JSON file} {--compare= : Compare audio and recording IDs with a prior snapshot}';

    protected $description = 'Read-only integrity check and snapshot of Quran profiles, recordings and sources';

    public function handle(): int
    {
        $tables = ['quran_reciter_profiles', 'quran_reciter_profile_translations', 'quran_reciters', 'reciter_translations', 'quran_reciter_aliases', 'quran_recording_sources', 'quran_recitations'];
        $data = DB::transaction(function () use ($tables): array {
            $data = [];
            foreach ($tables as $table) {
                $data[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
            }

            return $data;
        });
        $errors = [];
        $sources = array_column($data['quran_recording_sources'], null, 'id');
        $recordings = array_column($data['quran_reciters'], null, 'id');
        foreach ($data['quran_recitations'] as $audio) {
            $source = $sources[$audio['source_id']] ?? null;
            if (! $source || $source['recording_id'] !== $audio['reciter_id'] || ! isset($recordings[$audio['reciter_id']])) {
                $errors[] = 'Audio ownership mismatch: '.$audio['id'];
            }
        }
        if ($path = $this->option('compare')) {
            $before = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            foreach (['quran_reciters', 'quran_recitations'] as $table) {
                $now = array_column($data[$table], null, 'id');
                foreach ($before[$table] ?? [] as $row) {
                    if (! isset($now[$row['id']])) {
                        $errors[] = $table.' missing ID '.$row['id'];

                        continue;
                    }
                    foreach ($table === 'quran_recitations' ? ['reciter_id', 'surah_id', 'format', 'audio_url', 'duration_seconds', 'file_size_kb'] : ['slug', 'kind', 'style', 'riwayah'] as $key) {
                        if (($row[$key] ?? null) !== ($now[$row['id']][$key] ?? null)) {
                            $errors[] = $table.' changed '.$row['id'].':'.$key;
                        }
                    }
                }
            }
        }
        if ($path = $this->option('snapshot')) {
            if (file_exists($path)) {
                $this->error('Snapshot already exists; choose a new path.');

                return self::FAILURE;
            }
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            chmod($path, 0600);
        }
        $this->table(['Table', 'Rows'], array_map(fn ($table) => [$table, count($data[$table])], $tables));
        foreach ($errors as $error) {
            $this->error($error);
        }
        $this->info(count($errors).' integrity differences.');

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }
}
