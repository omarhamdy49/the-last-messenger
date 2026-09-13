<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quran_reciters')->orderBy('id')->chunkById(100, function ($recordings): void {
            foreach ($recordings as $recording) {
                $sourceId = null;
                if ($recording->mp3quran_folder) {
                    $sourceId = $this->source($recording->id, 'mp3quran', 'surah', 'legacy:'.$recording->id, [
                        'base_url' => $recording->mp3quran_folder,
                        'external_timing_id' => $recording->mp3quran_read_id,
                    ]);
                }
                if ($recording->everyayah_folder) {
                    $this->source($recording->id, 'everyayah', 'ayah', 'catalogue', [
                        'base_url' => 'https://everyayah.com/data', 'metadata' => json_encode(['folder' => $recording->everyayah_folder]),
                    ]);
                }
                foreach (DB::table('quran_recitations')->where('reciter_id', $recording->id)->get() as $audio) {
                    $owner = $sourceId;
                    if (! $owner || ! str_starts_with($audio->audio_url, rtrim($recording->mp3quran_folder, '/').'/')) {
                        $base = substr($audio->audio_url, 0, (int) strrpos($audio->audio_url, '/') + 1);
                        $owner = $this->source($recording->id, 'direct', 'surah', hash('sha256', $base), ['base_url' => $base]);
                    }
                    DB::table('quran_recitations')->where('id', $audio->id)->update(['source_id' => $owner]);
                }
            }
        });
        if (DB::table('quran_recitations')->whereNull('source_id')->exists()) {
            throw new RuntimeException('Unmapped Quran audio remains; source migration stopped.');
        }
        Schema::table('quran_recitations', function (Blueprint $table): void {
            $table->unsignedBigInteger('source_id')->nullable(false)->change();
            $table->dropUnique(['reciter_id', 'surah_id', 'format']);
            $table->unique(['source_id', 'surah_id', 'format']);
        });
        Schema::table('quran_reciters', function (Blueprint $table): void {
            $table->dropIndex('quran_reciters_mp3quran_read_id_index');
            $table->dropColumn(['mp3quran_read_id', 'mp3quran_folder', 'everyayah_folder']);
        });
    }

    private function source(int $recording, string $provider, string $kind, string $key, array $values): int
    {
        $identity = ['recording_id' => $recording, 'provider' => $provider, 'kind' => $kind, 'source_key' => $key];
        DB::table('quran_recording_sources')->updateOrInsert($identity, $values + ['created_at' => now(), 'updated_at' => now()]);

        return (int) DB::table('quran_recording_sources')->where($identity)->value('id');
    }

    public function down(): void
    {
        throw new RuntimeException('Multiple audio sources cannot be represented by the legacy columns. Restore the pre-migration snapshot or deploy a forward fix.');
    }
};
