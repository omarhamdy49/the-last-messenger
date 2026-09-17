<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Shared\Repositories\FavoriteRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Folds one reciter profile (the person) into another: the absorbed profile's
 * recordings become recordings of the kept profile and the absorbed one is deleted.
 */
final class ReciterProfileMergeService
{
    public function __construct(private readonly FavoriteRepositoryInterface $favorites) {}

    /** @return int recordings moved */
    public function merge(ReciterProfile $target, ReciterProfile $source): int
    {
        if ($target->is($source)) {
            throw new InvalidArgumentException('A reciter cannot be merged into itself.');
        }

        return DB::transaction(function () use ($target, $source): int {
            $source->load('translations');
            $target->load('translations');

            // Keep the old public URL alive: the reciter page resolves an alias slug to
            // a recording and 301s to that recording's (now the kept) profile.
            // Prefer an active recording (the page 404s on inactive ones), the old default first.
            $landing = $source->recordings()->without('sources')->orderByDesc('is_active')
                ->orderByRaw('id = ? desc', [$source->default_recording_id ?? 0])->value('id');
            if ($landing) {
                DB::table('quran_reciter_aliases')->updateOrInsert(['slug' => $source->slug], ['recording_id' => $landing, 'created_at' => now(), 'updated_at' => now()]);
            }

            $moved = DB::table('quran_reciters')->where('profile_id', $source->id)->update(['profile_id' => $target->id, 'updated_at' => now()]);

            // The kept profile's own data wins; the absorbed one only fills gaps.
            foreach ($source->translations as $translation) {
                $own = $target->translations->firstWhere('locale', $translation->locale);
                if (! $own) {
                    $target->translations()->create($translation->only('locale', 'name', 'description'));
                } else {
                    $own->update([
                        'name' => filled($own->name) ? $own->name : $translation->name,
                        'description' => filled($own->description) ? $own->description : $translation->description,
                    ]);
                }
            }

            $this->favorites->reassign(ReciterProfile::class, $source->id, $target->id);

            $target->fill([
                'country' => $target->country ?: $source->country,
                'profile_image' => $target->profile_image ?: $source->profile_image,
                'is_featured' => $target->is_featured || $source->is_featured,
                'followers_count' => (int) $target->followers_count + (int) $source->followers_count,
                'default_recording_id' => $target->default_recording_id ?? $source->default_recording_id,
            ])->save(); // saved event flushes the reciter caches

            $source->delete();

            return $moved;
        });
    }
}
