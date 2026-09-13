<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReciterGroupingService
{
    private ?array $identities = null;

    /** Catalogue folder matches are evidence; spelling similarity is never a merge rule. */
    public function identity(Reciter $recording): array
    {
        $this->identities ??= json_decode(file_get_contents(database_path('data/quran-source-identities.json')), true, flags: JSON_THROW_ON_ERROR);
        if ($recording->slug === 'husary-muallim') {
            return ['key' => 'mp3quran:118', 'slug' => 'husary', 'name_ar' => 'محمود خليل الحصري', 'name_en' => 'Mahmoud Khalil Al-Hussary'];
        }
        foreach ($recording->sources as $source) {
            $known = $this->identities[$source->base_url] ?? null;
            if ($source->external_reciter_id || $known) {
                $external = $source->external_reciter_id ?? $known['external_reciter_id'];
                $provider = $source->provider === 'direct' && $known ? $known['provider'] : $source->provider;
                $overrides = ['mp3quran:118' => 'husary', 'mp3quran:112' => 'minshawy', 'mp3quran:51' => 'abdul-basit'];
                $key = $provider.':'.$external;

                return ['key' => $key, 'slug' => $overrides[$key] ?? Str::slug($known['name_en'] ?? $recording->getDisplayName('en')) ?: $recording->slug,
                    'name_ar' => $known['name_ar'] ?? ($source->metadata['person_name_ar'] ?? null),
                    'name_en' => $known['name_en'] ?? ($source->metadata['person_name_en'] ?? null)];
            }
        }

        return ['key' => 'recording:'.$recording->id, 'slug' => $recording->slug, 'name_ar' => null, 'name_en' => null];
    }

    public function attach(Reciter $recording): ?ReciterProfile
    {
        if ($recording->kind !== 'recitation') {
            return null;
        }

        return DB::transaction(function () use ($recording): ReciterProfile {
            $identity = $this->identity($recording);
            $old = $recording->profile_id ? ReciterProfile::find($recording->profile_id) : null;
            // An administrator's explicit grouping has precedence over imports.
            if ($old && $old->identity_key !== 'recording:'.$recording->id) {
                $profile = $old;
            } else {
                $profile = ReciterProfile::where('identity_key', $identity['key'])->first();
                if (! $profile) {
                    $slug = $identity['slug'];
                    $reserved = ReciterProfile::where('slug', $slug)->where('id', '!=', $old?->id ?? 0)->exists()
                        || Reciter::where('slug', $slug)->where('id', '!=', $recording->id)->whereNotIn('id', $this->sameIdentityIds($identity['key']))->exists();
                    if ($reserved) {
                        $slug .= '-qari-'.$recording->id;
                    }
                    $profile = $old ?? new ReciterProfile;
                    $profile->fill([
                        'identity_key' => $identity['key'], 'slug' => $slug, 'country' => $recording->country,
                        'profile_image' => $recording->profile_image, 'is_active' => $recording->is_active,
                        'is_featured' => $recording->is_featured, 'display_order' => $recording->display_order,
                        'followers_count' => $recording->followers_count ?? 0, 'default_recording_id' => $recording->id,
                    ])->save();
                }
            }
            DB::table('quran_reciters')->where('id', $recording->id)->update(['profile_id' => $profile->id]);
            $recording->profile_id = $profile->id;
            DB::table('quran_reciter_aliases')->updateOrInsert(['slug' => $recording->slug], ['recording_id' => $recording->id, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($recording->translations as $translation) {
                $name = $identity['name_'.$translation->locale] ?? $translation->name;
                if (! $name) {
                    continue;
                }
                if ($profile->identity_key === 'recording:'.$recording->id) {
                    $profile->translations()->updateOrCreate(['locale' => $translation->locale], ['name' => $name, 'description' => $translation->description]);
                } else {
                    $profile->translations()->firstOrCreate(['locale' => $translation->locale], ['name' => $name, 'description' => $translation->description]);
                }
            }
            foreach (['ar', 'en'] as $locale) {
                if ($identity['name_'.$locale] ?? null) {
                    $profile->translations()->firstOrCreate(['locale' => $locale], ['name' => $identity['name_'.$locale]]);
                }
            }
            if ($old && $old->id !== $profile->id && ! $old->recordings()->exists()) {
                $old->delete();
            }
            QuranCacheKeys::flushReciters();

            return $profile;
        });
    }

    private function sameIdentityIds(string $identity): array
    {
        // Used only when allocating a canonical slug, never in public request lists.
        return Reciter::whereHas('sources', fn ($q) => $q->where('external_reciter_id', Str::after($identity, ':'))->where('provider', Str::before($identity, ':')))->pluck('id')->all();
    }
}
